<?php


namespace App\WebSocket;


use App\Service\OnlineUser;
use App\utils\enum\WebSocketAction;
use App\utils\helper\Gravatar;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Logger\LoggerFactory;
use Nette\Utils\Random;
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
        $this->delOnlineUserAndBroadcastOtherUsers($server, $fd);
    }

    public function onMessage(WebSocketServer $server, Frame $frame): void
    {
        $this->parserAction($server, $frame);
    }

    public function onOpen(WebSocketServer $server, Request $request): void
    {
        if (isset($request->get['username']) && !empty($request->get['username'])) {
            $username = $request->get['username'];
            $email = $request->get['emial'] ?? $username . '@qq.com';
            $avatar = Gravatar::makeGravatar($email);
        } else {
            $random = Random::generate(8);
            $username = '神秘乘客' . $random;
            $avatar = Gravatar::makeGravatar($random . '@qq.com');
        }

        // 插入在线用户列表并广播给所有用户
        $this->addOnlineUserAndBroadcastOtherUsers($server, $request, $username, $avatar);
        $this->showPersonalInfo($server, $request, $username);

    }

    // 解析行为
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
        try {
            if (!class_exists($controllerClass)) {
                $controllerClass = "\\App\\WebSocket\\Controller\\Index";
            }
            $ref = new \ReflectionClass($controllerClass);
            if (!$ref->hasMethod($action)) {
                $action = 'actionNotFound';
                $params = $payload;
            }
            $obj = new $controllerClass($server, $frame, $this->container);
            call_user_func_array([$obj, $action], $params);
        } catch (\ReflectionException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    // 添加新用户并广播给其他用户
    protected function addOnlineUserAndBroadcastOtherUsers(WebSocketServer $server, Request $request, $username, $avatar)
    {
        $currentFd = $request->fd;
        $userInfo = [
            'fd' => $currentFd,
            'username' => $username,
            'avatar' => $avatar,
            'last_heartbeat' => time(),
        ];
        OnlineUser::setUser($currentFd, $userInfo);
        $data = json_encode([
            'fd' => $currentFd,
            'content' => $userInfo,
            'action' => WebSocketAction::USER_IN_ROOM,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->broadcastAllUserInfo($server, $currentFd, $data);
    }

    // 展示用户信息
    protected function showPersonalInfo(WebSocketServer $server, Request $request, $username)
    {
        if (empty($request->get['is_reconnection']) || $request->get['is_reconnection'] == 0) {
            $content = "{$username}，欢迎乘坐1号特快列车，请系好安全带，文明乘车.";
            $data = json_encode([
                'content' => $content,
                'action' => WebSocketAction::BROADCAST_ADMIN,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->logger->debug("广播: 向用户 {$request->fd} 发送消息. 数据: {$data}");
            $server->push($request->fd, $data);
        }
    }

    // 删除离线用户并广播给其他人
    protected function delOnlineUserAndBroadcastOtherUsers(Server $server, int $fd)
    {
        OnlineUser::delUser($fd);
        $data = json_encode([
            'fd' => $fd,
            'content' => '',
            'action' => WebSocketAction::USER_OUT_ROOM,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->broadcastAllUserInfo($server, $fd, $data);
    }

    // 向其他人广播信息
    protected function broadcastAllUserInfo(Server $server, $exceptFd, $data)
    {
        $users = OnlineUser::getAllUser();
        foreach ($users as $fd => $user) {
            if ($exceptFd == $fd) {
                continue;
            }
            if (!$server->exist($fd)) {
                OnlineUser::delUser($fd);
                continue;
            }
            $this->logger->debug("广播: 向用户 {$fd} 发送消息. 数据: {$data}");
            $server->push($fd, $data);
        }
    }

}