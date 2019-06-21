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
     * id映射
     * @var array
     */
    protected static $idMap = [];

    /**
     * tid计数
     * @var array
     */
    protected static $tidCount = [];

    /**
     * 获取协程id
     * @return int
     */
    public static function id()
    {
        $id = -1;
        if (!class_exists('\Swoole\Coroutine')) {
            return $id;
        }
        return \Swoole\Coroutine::getuid();
    }

    /**
     * 获取顶部协程id
     * @return int
     */
    public static function tid()
    {
        $id = static::id();
        return static::$idMap[$id] ?? $id;
    }

    /**
     * 启用协程钩子
     * @param int $flags
     */
    public static function enableHook(int $flags = SWOOLE_HOOK_ALL)
    {
        static $trigger = false;
        if (!$trigger) {
            \Swoole\Runtime::enableCoroutine(true, $flags); // Swoole >= 4.1.0
            $trigger = true;
        }
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
        $tid = static::tid();
        $top = $tid == static::id();
        static::go($function, $params, $tid, $top);
    }

    /**
     * 创建协程 (手动)
     * @param $function
     * @param $params
     * @param $tid
     * @param $top
     */
    public static function go($function, $params, $tid, $top)
    {
        $isMix = class_exists(\Mix::class);
        go(function () use ($function, $params, $tid, $top, $isMix) {
            // 记录协程id关系
            $id = static::id();
            if ($top && $tid == -1) {
                $tid = $id;
            }
            static::$idMap[$id]     = $tid;
            static::$tidCount[$tid] = static::$tidCount[$tid] ?? 0;
            static::$tidCount[$tid]++;
            // 执行闭包
            try {
                call_user_func_array($function, $params);
            } catch (\Throwable $e) {
                // 错误处理
                if (!$isMix) {
                    throw $e;
                }
                // Mix错误处理
                \Mix::$app->error->handleException($e);
            } finally {
                // 清理协程资源
                unset(static::$idMap[$id]);
                static::$tidCount[$tid]--;
                // 清除协程
                if (static::$tidCount[$tid] == 0) {
                    unset(static::$tidCount[$tid]);
                    // Mix容器处理
                    if ($isMix) {
                        \Mix::$app->container->delete($tid);
                    }
                }
            }
        });
    }

}
