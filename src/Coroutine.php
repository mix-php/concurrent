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
     * 启用协程钩子
     * @param int $flags
     */
    public static function enableHook(int $flags = SWOOLE_HOOK_ALL)
    {
        \Swoole\Runtime::enableCoroutine(true, $flags); // Swoole >= 4.1.0
    }

    /**
     * 协程设置
     * @param array $config
     * @return mixed
     */
    public static function set(array $config)
    {
        return swoole_async_set($config);
    }

    /**
     * 创建协程 (自动)
     * @param callable $function
     * @param mixed ...$params
     */
    public static function create(callable $function, ...$params)
    {
        go(function () use ($function, $params) {
            // 执行闭包
            try {
                call_user_func_array($function, $params);
            } catch (\Throwable $e) {
                $isMix = class_exists(\Mix::class) && class_exists(\Mix\Console\Error::class);
                // 错误处理
                if (!$isMix) {
                    throw $e;
                }
                // Mix错误处理
                /** @var \Mix\Console\Error $error */
                $error = \Mix::$app->get('error');
                $error->handleException($e);
            }
        });
    }

}
