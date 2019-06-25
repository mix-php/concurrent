<?php

namespace Mix\Concurrent;

use Mix\Console\Error;

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
     * 禁用内置协程
     */
    public static function disableBuiltin()
    {
        // 兼容非 Swoole Console
        if (!function_exists('swoole_async_set')) {
            return;
        }
        // 禁用
        static $trigger = false;
        if (!$trigger) {
            swoole_async_set([
                'enable_coroutine' => false,
            ]);
            $trigger = true;
        }
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
                // 错误处理
                if (!class_exists(\Mix::class)) {
                    throw $e;
                }
                // Mix错误处理
                /** @var Error $error */
                $error = \Mix::$app->get('error');
                $error->handleException($e);
            }
        });
    }

}
