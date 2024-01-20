<?php
namespace Zettle\Support;

class Arr
{
    /**
     * Retrieve a value from the array/
     */
    public static function get(array $array, string $key, $default = null)
    {
        return $array[$key] ?? $default;
    }

    /**
     * Pluck a value from an array.
     */
    public static function pluck(array $array, string $key): array
    {
        return array_map(function (array $item) use ($key) {
            return self::get($item, $key);
        }, $array);
    }
}