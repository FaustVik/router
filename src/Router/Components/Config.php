<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components;

use FaustVik\Router\Cache\CachedMatching;
use FaustVik\Router\Cache\FileCache;
use FaustVik\Router\interfaces\Cache\CacheInterface;
use FaustVik\Router\interfaces\DI\RouterContainerInterface;
use FaustVik\Router\interfaces\Router\Components\CheckHttpMethodInterface;
use FaustVik\Router\interfaces\Router\Components\ConfigInterface;
use FaustVik\Router\interfaces\Router\Components\MatchingRouteInterface;
use FaustVik\Router\interfaces\Router\Components\RunnerInterface;
use FaustVik\Router\Router\Components\matching\OptimizedMatching;

/**
 * Router configuration class
 *
 * Manages router components configuration:
 * - Route runner (controller/closure execution)
 * - HTTP method checker
 * - Route matching strategy
 * - Cache settings
 * - DI container
 *
 * @package FaustVik\Router\Router\Components
 */
final class Config implements ConfigInterface
{
    private RunnerInterface $runner;
    private CheckHttpMethodInterface $checker;
    private MatchingRouteInterface $match;
    private ?CacheInterface $cache = null;
    private bool $cacheEnabled = false;
    private int $cacheTtl = 3600;
    private ?RouterContainerInterface $container = null;

    public function __construct()
    {
        $this->runner  = new Runner();
        $this->checker = new CheckerHttpMethod();
        $this->match   = new CachedMatching(new OptimizedMatching(), new FileCache());
    }

    public function setRunner(RunnerInterface $runner): void
    {
        $this->runner = $runner;
    }

    public function getRunner(): RunnerInterface
    {
        return $this->runner;
    }

    public function setCheckerHttpMethod(CheckHttpMethodInterface $checker): void
    {
        $this->checker = $checker;
    }

    public function getCheckerHttpMethod(): CheckHttpMethodInterface
    {
        return $this->checker;
    }

    public function getMatch(): MatchingRouteInterface
    {
        return $this->match;
    }

    public function setMatcher(MatchingRouteInterface $matcher): void
    {
        $this->match = $matcher;
    }

    public function enableCache(): void
    {
        $this->cacheEnabled = true;
        if ($this->match instanceof CachedMatching) {
            $this->match->enableCache();
        }
    }

    public function disableCache(): void
    {
        $this->cacheEnabled = false;
        if ($this->match instanceof CachedMatching) {
            $this->match->disableCache();
        }
    }

    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
        if ($this->match instanceof CachedMatching) {
            $this->match->setCache($cache);
        }
    }

    public function getCache(): ?CacheInterface
    {
        return $this->cache;
    }

    public function setCacheTtl(int $ttl): void
    {
        $this->cacheTtl = $ttl;
        if ($this->match instanceof CachedMatching) {
            $this->match->setCacheTtl($ttl);
        }
    }

    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    public function clearCache(): bool
    {
        if ($this->match instanceof CachedMatching) {
            return $this->match->clearCache();
        }
        return false;
    }

    public function setContainer(?RouterContainerInterface $container): void
    {
        $this->container = $container;

        // Update runner with container
        if ($this->runner instanceof Runner) {
            $this->runner->setContainer($container);
        }
    }

    public function getContainer(): ?RouterContainerInterface
    {
        return $this->container;
    }
}
