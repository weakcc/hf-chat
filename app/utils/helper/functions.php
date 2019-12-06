<?php

declare(strict_types=1);

use Hyperf\Framework\Logger\StdoutLogger;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Context;

if (!function_exists('test')) {
    function test()
    {
        return "测试代码";
    }
}

if (!function_exists('di')) {
    /**
     * @return \Psr\Container\ContainerInterface
     */
    function di()
    {
        return ApplicationContext::getContainer();
    }
}

if (!function_exists('logger')) {
    /**
     * 日志记录
     * @param string $name
     * @return \Psr\Log\LoggerInterface
     */
    function logger($name = 'default')
    {
        return di()->get(LoggerFactory::class)->get($name, $name);
    }
}

if (!function_exists('redis')) {
    /**
     * Redis
     * @param string $name
     * @return \Hyperf\Redis\RedisProxy|Redis
     */
    function redis($name = 'default')
    {
        return di()->get(RedisFactory::class)->get($name);
    }
}

if (!function_exists('setContext')) {
    /**
     * 设置当前 key value 写入请求上下文
     * @param $key
     * @param $value
     * @return mixed
     */
    function setContext($key, $value)
    {
        return Context::set($key, $value);
    }
}

if (!function_exists('getContext')) {
    /**
     * 获取当前 key 对应的上下文
     * @param $key
     * @return mixed|null
     */
    function getContext($key)
    {
        return Context::get($key);
    }
}

if (!function_exists('hasContext')) {
    /**
     * 判断 key 是否在当前上下文
     * @param $key
     * @return bool
     */
    function hasContext($key)
    {
        return Context::has($key);
    }
}

if (!function_exists('stdout')) {
    /**
     * 终端日志
     * @return StdoutLogger|mixed
     */
    function stdout()
    {
        return di()->get(StdoutLogger::class);
    }
}

if (!function_exists('dd')) {
    /**
     * 终端打印调试
     * @param $data
     */
    function dd($data)
    {
        stdout()->info("-----------------打印调试开启-----------------");
        print_r($data);
        echo PHP_EOL;
        stdout()->info("-----------------打印调试结束-----------------");
    }
}