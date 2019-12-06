<?php


namespace App\utils\helper;


class Gravatar
{
    /**
     * 生成一个 Gravatar 头像
     * @param string $email
     * @param int $size
     * @return string
     */
    public static function makeGravatar(string $email, int $size = 120)
    {
        $hash = md5($email);
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=identicon";
    }
}