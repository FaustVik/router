<?php

declare(strict_types=1);

namespace FaustVik\Router\Interfaces\Router\Components;

use FaustVik\Router\Interfaces\Cache\CacheInterface;
use FaustVik\Router\Interfaces\DI\RouterContainerInterface;

/**
 * Интерфейс конфигурации роутера
 *
 * Определяет методы для настройки компонентов роутера:
 * - Настройка выполнения маршрутов (Runner)
 * - Проверка HTTP методов
 * - Поиск маршрутов (Matching)
 * - Настройка кеширования
 * - Настройка Dependency Injection
 *
 * @package FaustVik\Router\Interfaces\Router\Components
 */
interface ConfigInterface
{
    // ============================================================================
    // Runner methods - Методы для работы с выполнением маршрутов
    // ============================================================================

    /**
     * Устанавливает кастомный компонент для выполнения маршрутов
     *
     * @param RunnerInterface $runner Компонент для выполнения контроллеров
     * @return void
     */
    public function setRunner(RunnerInterface $runner): void;

    /**
     * Получает компонент для выполнения маршрутов
     *
     * @return RunnerInterface Компонент для выполнения контроллеров
     */
    public function getRunner(): RunnerInterface;

    // ============================================================================
    // HTTP Method Checker methods - Методы для проверки HTTP методов
    // ============================================================================

    /**
     * Устанавливает проверщик разрешенных HTTP методов
     *
     * @param CheckHttpMethodInterface $checker Проверщик HTTP методов
     * @return void
     */
    public function setCheckerHttpMethod(CheckHttpMethodInterface $checker): void;

    /**
     * Получает проверщик разрешенных HTTP методов
     *
     * @return CheckHttpMethodInterface Проверщик HTTP методов
     */
    public function getCheckerHttpMethod(): CheckHttpMethodInterface;

    // ============================================================================
    // Route Matching methods - Методы для поиска маршрутов
    // ============================================================================

    /**
     * Устанавливает кастомный компонент для поиска маршрутов
     *
     * @param MatchingRouteInterface $matcher Компонент для поиска маршрутов
     * @return void
     */
    public function setMatcher(MatchingRouteInterface $matcher): void;

    /**
     * Получает компонент для поиска маршрутов
     *
     * @return MatchingRouteInterface Компонент для поиска маршрутов
     */
    public function getMatch(): MatchingRouteInterface;

    // ============================================================================
    // Cache methods - Методы для работы с кешированием
    // ============================================================================

    /**
     * Включает кеширование маршрутов
     *
     * @return void
     */
    public function enableCache(): void;

    /**
     * Отключает кеширование маршрутов
     *
     * @return void
     */
    public function disableCache(): void;

    /**
     * Проверяет, включено ли кеширование
     *
     * @return boolean true если кеширование включено
     */
    public function isCacheEnabled(): bool;

    /**
     * Устанавливает драйвер кеша
     *
     * @param CacheInterface $cache Драйвер кеша
     * @return void
     */
    public function setCache(CacheInterface $cache): void;

    /**
     * Получает драйвер кеша
     *
     * @return CacheInterface|null Драйвер кеша или null если не установлен
     */
    public function getCache(): ?CacheInterface;

    /**
     * Устанавливает время жизни кеша (TTL)
     *
     * @param integer $ttl Время жизни кеша в секундах
     * @return void
     */
    public function setCacheTtl(int $ttl): void;

    /**
     * Получает время жизни кеша (TTL)
     *
     * @return integer Время жизни кеша в секундах
     */
    public function getCacheTtl(): int;

    /**
     * Очищает кеш маршрутов
     *
     * @return boolean true если кеш успешно очищен
     */
    public function clearCache(): bool;

    // ============================================================================
    // DI Container methods - Методы для работы с Dependency Injection
    // ============================================================================

    /**
     * Устанавливает DI контейнер
     *
     * @param RouterContainerInterface|null $container DI контейнер
     * @return void
     */
    public function setContainer(?RouterContainerInterface $container): void;

    /**
     * Получает DI контейнер
     *
     * @return RouterContainerInterface|null DI контейнер или null если не установлен
     */
    public function getContainer(): ?RouterContainerInterface;
}
