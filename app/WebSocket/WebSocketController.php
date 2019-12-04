<?php


namespace App\WebSocket;


use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;
use Swoole\Http\Request;
use Swoole\Websocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;
use Swoole\Server;

class WebSocketController implements
    OnMessageInterface,
    OnOpenInterface,
    OnCloseInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get(StdoutLoggerInterface::class);
//        $this->logger = $this->container->get(LoggerFactory::class)->get('ws');
    }

    public function onClose(Server $server, int $fd, int $reactorId): void
    {
        var_dump($fd . " closed. ");
    }

    public function onMessage(WebSocketServer $server, Frame $frame): void
    {
        $this->parserAction($server, $frame);
    }

    public function onOpen(WebSocketServer $server, Request $request): void
    {
        // $server->push($request->fd, 'Opened');
        $token = $request->fd;
    }

    protected function parserAction(WebSocketServer $server, Frame $frame)
    {
        $data = $frame->data;
        if ($data === 'PING') {
            $server->push($frame->fd, 'PONG');
            return;
        }
        $payload = json_decode($data, true);
        $class = $payload['controller'] ?? 'index';
        $action = $payload['action'] ?? 'actionNotFound';
        $params = isset($payload['params']) ? (array)$payload['params'] : [];
        $controllerClass = "\\App\\WebSocket\\Controller\\" . ucfirst($class);
//        if (!class_exists($controllerClass)) {
//            $controllerClass = "\\App\\WebSocket\\Controller\\Index";
//        }
//        if (method_exists($controllerClass, $action)) {
//            $action = 'actionNotFound';
//        }
        try {
            if (!class_exists($controllerClass)) {
                $controllerClass = "\\App\\WebSocket\\Controller\\Index";
            }
            $ref = new \ReflectionClass($controllerClass);
            if (!$ref->hasMethod($action)) {
                $action = 'actionNotFound';
            }
            $obj = new $controllerClass($server, $frame, $this->container);
            call_user_func_array([$obj, $action], $params);
        } catch (\ReflectionException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}