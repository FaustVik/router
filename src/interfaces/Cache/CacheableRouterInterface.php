<?php

declare(strict_types=1);

namespace FaustVik\Router\interfaces\Cache;

interface CacheableRouterInterface
{
    /**
     * Включить кеширование
     */
    public function enableCache(): void;

    /**
     * Отключить кеширование
     */
    public function disableCache(): void;

    /**
     * Проверить, включено ли кеширование
     */
    public function isCacheEnabled(): bool;

    /**
     * Установить кеш-драйвер
     */
    public function setCache(CacheInterface $cache): void;

    /**
     * Получить кеш-драйвер
     */
    public function getCache(): ?CacheInterface;

    /**
     * Очистить кеш роутов
     */
    public function clearRouteCache(): bool;

    /**
     * Получить ключ кеша для роутов
     */
    public function getCacheKey(): string;
}
