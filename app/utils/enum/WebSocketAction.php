<?php


namespace App\utils\enum;


class WebSocketAction
{
    // 1xx 广播类消息
    const BROADCAST_ADMIN = 101;   // 管理消息
    const BROADCAST_SYSTEM = 102;  // 系统消息
    const BROADCAST_MESSAGE = 103; // 用户消息
    const BROADCAST_LAST_MESSAGE = 104; // 最后消息

    // 2xx 用户类消息
    const USER_INFO = 201;         // 用户信息
    const USER_ONLINE = 202;       // 在线列表
    const USER_IN_ROOM = 203;      // 进入房间
    const USER_OUT_ROOM = 204;     // 离开房间
}