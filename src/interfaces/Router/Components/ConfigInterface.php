<?php

namespace FaustVik\Router\interfaces\Router\Components;

use FaustVik\Router\interfaces\Cache\CacheInterface;

interface ConfigInterface
{
    /**
     * Set custom Runner
     *
     * @param RunnerInterface $runner
     *
     * @return void
     */
    public function setRunner(RunnerInterface $runner): void;

    /**
     * Get custom Runner or get default Runner
     *
     * @return RunnerInterface
     */
    public function getRunner(): RunnerInterface;

    /**
     * Set checker for allow http methods
     *
     * @param CheckHttpMethodInterface $checker
     *
     * @return void
     */
    public function setCheckerHttpMethod(CheckHttpMethodInterface $checker): void;

    /**
     * Get checker for allow http methods
     *
     * @return CheckHttpMethodInterface
     */
    public function getCheckerHttpMethod(): CheckHttpMethodInterface;

    /**
     * Set custom matcher uri with route
     *
     * @param MatchingRouteInterface $matcher
     *
     * @return void
     */
    public function setMatcher(MatchingRouteInterface $matcher): void;

    /**
     * Get matcher
     *
     * @return MatchingRouteInterface
     */
    public function getMatch(): MatchingRouteInterface;

    /**
     * Enable route caching
     *
     * @return void
     */
    public function enableCache(): void;

    /**
     * Disable route caching
     *
     * @return void
     */
    public function disableCache(): void;

    /**
     * Check if caching is enabled
     *
     * @return bool
     */
    public function isCacheEnabled(): bool;

    /**
     * Set cache driver
     *
     * @param CacheInterface $cache
     * @return void
     */
    public function setCache(CacheInterface $cache): void;

    /**
     * Get cache driver
     *
     * @return CacheInterface|null
     */
    public function getCache(): ?CacheInterface;

    /**
     * Set cache TTL
     *
     * @param int $ttl
     * @return void
     */
    public function setCacheTtl(int $ttl): void;

    /**
     * Get cache TTL
     *
     * @return int
     */
    public function getCacheTtl(): int;

    /**
     * Clear route cache
     *
     * @return bool
     */
    public function clearCache(): bool;
}
