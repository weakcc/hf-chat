<?php


namespace App\WebSocket;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;
use Swoole\Websocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;

class BaseController
{
    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var WebSocketServer
     */
    protected $server;

    /**
     * @var Frame
     */
    protected $frame;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(WebSocketServer $server, Frame $frame, ContainerInterface $container)
    {
        $this->server = $server;
        $this->frame = $frame;
        $this->container = $container;
        $this->logger = $container->get(StdoutLoggerInterface::class);
//        $this->logger = $container->get(LoggerFactory::class)->get('ws');
    }


    /**
     * 向指定用户发送信息
     * @param int $fd
     * @param $data
     * @param int $opcode
     * @param bool $finish
     * @return bool
     */
    public function push(int $fd, $data, int $opcode = 1, bool $finish = true)
    {
        if (!$this->exist($fd)) {
            return false;
        }
        $this->logger->debug("广播: 向用户 {$fd} 发送消息. 数据: {$data}");
        return $this->server->push($fd, $data, $opcode, $finish);
    }

    /**
     * 发送消息给指定的用户
     * @param int $receiver
     * @param string $data
     * @param int $sender
     * @return bool
     */
    public function sendTo(int $receiver, string $data, int $sender = -1) : bool
    {
        $fromUser = $sender < 0 ? 'SYSTEM' : $sender;
        if (!$this->exist($receiver)) {
            return false;
        }
        $this->logger->debug("广播: {$fromUser} 向用户{ $receiver} 发送消息. 数据: {$data}");
        return $this->push($receiver, $data, 1, true);
    }

    /**
     * 发送消息给在线所有用户
     * @param string $data
     * @param int $pageSize
     * @return int
     */
    public function sendToAll(string $data, int $sender = 0, $pageSize = 50): int
    {
        $count = 0;
        $connList = $this->getConnectionList($pageSize);
        $fromUser = $sender < 1 ? 'SYSTEM' : $sender;
        $this->logger->debug("广播: {$fromUser} 向所有用户发送消息. 消息: {$data}");
        if ($connList) {
            foreach ($connList as $fd) {
                if (!$this->exist($fd)) {
                    continue;
                }
                $info = $this->getClientInfo($fd);
                if (isset($info['websocket_status']) && $info['websocket_status'] > 0) {
                    $count++;
                    $this->push($fd, $data);
                }
            }
        }
        return $count;
    }

    /**
     * 发送消息给指定用户
     * @param string $data
     * @param array $receivers
     * @param array $excluded
     * @param int $sender
     * @return int
     */
    public function sendToSome(string $data, array $receivers = [], array $excluded = [], int $sender = 0): int
    {
        $count = 0;
        $fromUser = $sender < 1 ? 'SYSTEM' : $sender;
        $receivers = array_diff($receivers, $excluded);
        if ($receivers) {
            $this->logger->debug("广播: {$fromUser} 给某个指定用户发送消息. 数据: {$data}");
            foreach ($receivers as $fd) {
                if (!$this->exist($fd)) {
                    continue;
                }
                $info = $this->getClientInfo($fd);
                if (isset($info['websocket_status']) && $info['websocket_status'] > 0) {
                    $count++;
                    $this->push($fd, $data);
                }
            }
        }
        return $count;
    }

    /**
     * 当前用户文件描述符
     * @return int
     */
    protected function getFd()
    {
        return $this->frame->fd;
    }

    /**
     * 判断是否在线
     * @param int $fd
     * @return bool
     */
    protected function exist(int $fd): bool
    {
        return $this->server->exist($fd);
    }

    /**
     * 获取 fd 详情
     * @param int $fd
     * @return array
     */
    protected function getClientInfo(int $fd): array
    {
        return $this->server->getClientInfo($fd) ?: [];
    }

    /**
     * 获取用户 id
     * @param int $fd
     * @return int
     */
    protected function getFdUser(int $fd)
    {
        return (int)$this->getClientInfo($fd)['uid'] ?? 0;
    }

    /**
     * 获取所有的客户端连接
     * @param int $pageSize
     * @return array
     */
    protected function getConnectionList(int $pageSize = 50): array
    {
        $start_fd = 0;
        $list = [];
        while (true) {
            $connList = $this->server->connection_list($start_fd, $pageSize);
            if ($connList === false || count($connList) === 0) {
                break;
            }
            $start_fd = end($connList);
            $list = array_merge($list, $connList);
        }
        return $list;
    }

    /**
     * 获取传入信息
     * @return string
     */
    protected function getData()
    {
        $data = json_decode($this->frame->data, true);
        return $data['params'] ?? '';
    }


}