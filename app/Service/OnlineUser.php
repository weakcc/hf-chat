<?php


namespace App\Service;


class OnlineUser
{
    const USER_LIST = 'online-users';

    /**
     * 获取所有用户
     * @return array
     */
    public static function getAllUser()
    {
        return redis()->hGetAll(self::USER_LIST);
    }

    /**
     * 设置一个用户
     * @param int $fd
     * @param $data
     * @return bool|int
     */
    public static function setUser(int $fd, $data)
    {
        return redis()->hSet(self::USER_LIST, (string)$fd, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * 获取一个用户
     * @param int $fd
     * @return string
     */
    public static function getUser(int $fd)
    {
        return redis()->hGet(self::USER_LIST, (string)$fd);
    }

    /**
     * 删除一个用户
     * @param int $fd
     * @return bool|int
     */
    public static function delUser(int $fd)
    {
        return redis()->hDel(self::USER_LIST, (string)$fd);
    }
}