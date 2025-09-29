<?php

namespace App\Services;

class PerformanceMonitor
{
    protected static $measurements = [];

    public static function start($name)
    {
        self::$measurements[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(),
        ];
    }

    public static function end($name)
    {
        if (!isset(self::$measurements[$name])) {
            return null;
        }

        $measurement = self::$measurements[$name];
        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $result = [
            'name' => $name,
            'time' => ($endTime - $measurement['start']) * 1000, // ms
            'memory' => ($endMemory - $measurement['memory_start']) / 1024 / 1024, // MB
        ];

        \Log::info("Performance: {$name}", $result);

        return $result;
    }

    public static function measure($name, callable $callback)
    {
        self::start($name);
        $result = $callback();
        self::end($name);

        return $result;
    }
}
