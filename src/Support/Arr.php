<?php
namespace Zettle\Support;

class Arr
{
    /**
     * Retrieve a value from the array/
     *
     * @param array|mixed  $array
     * @param array|string $key
     * @param mixed        $default
     */
    public static function get($array, $key, $default = null)
    {
        $keys = is_array($key) ? $key : explode(".", $key);

        foreach ($keys as $i => $segment) {
            unset($keys[$i]);

            // Handle wildcards.
            if ($segment === "*") {
                // We cannot iterate over scalar values.
                if (! is_iterable($array))
                    return $default;

                $result = [];

                foreach ($array as $item)
                    $result[] = Arr::get($item, $keys);

                return $result;
            }

            // Handle regular.
            if (is_array($array) && Arr::has($array, $segment)) {
                $array = $array[$segment];
            }
            else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Return the length of the array.
     */
    public static function len(array $array, ?string $key = null): int
    {
        return $key !== null
            ? count(Arr::get($array, $key, []))
            : count($array);
    }

    /**
     * Check if a key exists.
     */
    public static function has(array $array, string $key): bool
    {
        return array_key_exists($key, $array);
    }

    /**
     * Map over an array.
     */
    public static function map(array $array, callable $callback): array
    {
        return array_map($callback, array_values($array), array_keys($array));
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