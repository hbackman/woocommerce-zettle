<?php
namespace Zettle\Support;

use InvalidArgumentException;

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

    /**
     * Filter an array based on a callable.
     *
     * @param array           $array
     * @param string|callable $key
     * @param string|null     $operator
     * @param mixed           $value
     *
     * @return array
     */
    public static function where(array $array, $key, string $operator = null, $value = null): array
    {
        return array_values(array_filter(
           $array, self::filterForWhere($key, $operator, $value)
        ));
    }

    /**
     * Search an array for the first matching record.
     *
     * @param array           $array
     * @param string|callable $key
     * @param string|null     $operator
     * @param mixed           $value
     *
     * @return null|array
     */
    public static function first(array $array, $key, string $operator = null, $value = null): ?array
    {
        $callback = self::filterForWhere($key, $operator, $value);

        foreach ($array as $item) {
            if ($callback($item))
                return $item;
        }

        return null;
    }

    /**
     * Build a filter callback.
     *
     * @param string|callable $key
     * @param string|null     $operator
     * @param mixed           $value
     */
    private static function filterForWhere($key, string $operator = null, $value = null): callable
    {
        // Ensure that the key is not a callable string.
        if (false == is_string($key) && is_callable($key)) {
            return $key;
        }

        return function ($item) use ($key, $operator, $value) {
            $retrieved = Arr::get($item, $key);

            switch ($operator) {
                case '=':  return $retrieved == $value;
                case '!=': return $retrieved != $value;
                case '>':  return $retrieved > $value;
                case '<':  return $retrieved < $value;
                case '>=': return $retrieved >= $value;
                case '<=': return $retrieved <= $value;
                default:
                    throw new InvalidArgumentException("Invalid operator $operator.");
            }
        };
    }
}