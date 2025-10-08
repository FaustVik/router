<?php

declare(strict_types=1);

namespace FaustVik\Router\interfaces\Cache;

interface CacheInterface
{
    /**
     * Получить значение из кеша
     */
    public function get(string $key): mixed;

    /**
     * Сохранить значение в кеш
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool;

    /**
     * Проверить существование ключа в кеше
     */
    public function has(string $key): bool;

    /**
     * Удалить значение из кеша
     */
    public function delete(string $key): bool;

    /**
     * Очистить весь кеш
     */
    public function clear(): bool;

    /**
     * Получить множественные значения
     * 
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function getMultiple(array $keys): array;

    /**
     * Сохранить множественные значения
     * 
     * @param array<string, mixed> $values
     */
    public function setMultiple(array $values, int $ttl = 0): bool;

    /**
     * Удалить множественные значения
     * 
     * @param array<int, string> $keys
     */
    public function deleteMultiple(array $keys): bool;
}
