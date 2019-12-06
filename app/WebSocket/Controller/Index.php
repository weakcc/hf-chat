<?php


namespace App\WebSocket\Controller;


use App\Service\OnlineUser;
use App\utils\enum\WebSocketAction;
use App\WebSocket\BaseController;

class Index extends BaseController
{
    public function index()
    {
        var_dump($this->frame->fd);
    }

    public function actionNotFound(...$params)
    {
        $data = json_encode([
            'content' => json_encode($params) . ' 请求错误，请检查！',
            'action' => -1,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $this->push($this->getFd(), $data);
    }

    public function info()
    {
        $fd = $this->getFd();
        $data = json_encode([
            'content' => json_decode(OnlineUser::getUser($fd), true),
            'action' => WebSocketAction::USER_INFO,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $this->push($fd, $data);
    }

    /**
     * 在线用户
     */
    public function online()
    {
        $userList = OnlineUser::getAllUser();
        foreach ($userList as &$item) {
            $item = json_decode($item, true);
        }
        unset($item);
        $data = json_encode([
            'list' => $userList,
            'action' => WebSocketAction::USER_ONLINE,
        ]);
        unset($userList);
        return $this->push($this->getFd(), $data);
    }

    public function message($content, $type = 'text')
    {
        $userList = OnlineUser::getAllUser();
        foreach ($userList as $fd => $item) {
            if (!$this->exist($fd)) {
                continue;
            }
            $data = json_encode([
                'content' => $content,
                'action' => WebSocketAction::BROADCAST_MESSAGE,
                'type' => $type,
                'fromUserFd' => $this->getFd(),
                'sendTime' => date('Y-m-d H:i:s'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->push($fd, $data);
        }
        return;
    }
}