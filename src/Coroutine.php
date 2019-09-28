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

    /**
     * 延迟执行
     * @param callable $function
     */
    public static function defer(callable $function)
    {
        return \Swoole\Coroutine::defer(function () use ($function) {
            try {
                // 执行闭包
                call_user_func($function);
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
