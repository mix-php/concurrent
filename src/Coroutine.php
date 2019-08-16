<?php

namespace Mix\Concurrent;

/**
 * Class Coroutine
 * @package Mix\Concurrent
 * @author liu,jian <coder.keda@gmail.com>
 */
class Coroutine
{

    /**
     * 获取协程id
     * @return int
     */
    public static function id()
    {
        return \Swoole\Coroutine::getCid();
    }

    /**
     * 获取父协程id
     * @return int
     */
    public static function pid()
    {
        return \Swoole\Coroutine::getPcid();
    }

    /**
     * 协程设置
     * @param array $config
     */
    public static function set(array $config)
    {
        swoole_async_set($config);
    }

    /**
     * 创建协程
     * @param callable $function
     * @param mixed ...$params
     * @return int|false
     */
    public static function create(callable $function, ...$params)
    {
        return \Swoole\Coroutine::create(function () use ($function, $params) {
            try {
                // 执行闭包
                call_user_func_array($function, $params);
            } catch (\Throwable $e) {
                $isMix = class_exists(\Mix::class);
                // 错误处理
                if (!$isMix) {
                    throw $e;
                }
                // Mix错误处理
                /** @var \Mix\Console\Error $error */
                $error = \Mix::$app->context->get('error');
                $error->handleException($e);
            }
        });
    }

}
