<?php

declare(strict_types=1);

if (!function_exists('array_find')) {
    /**
     * Polyfill for PHP 8.4 array_find().
     *
     * @template T
     * @param array<T> $array
     * @param callable(T, array-key): bool $callback
     * @return T|null
     */
    function array_find(array $array, callable $callback): mixed
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }
        return null;
    }
}

if (!function_exists('array_all')) {
    /**
     * Polyfill for PHP 8.4 array_all().
     *
     * @param array<mixed> $array
     * @param callable(mixed, array-key): bool $callback
     */
    function array_all(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }
        return true;
    }
}
