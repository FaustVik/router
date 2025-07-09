<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components;

use FaustVik\Router\interfaces\Router\Components\CheckHttpMethodInterface;
use FaustVik\Router\interfaces\Router\Components\ConfigInterface;
use FaustVik\Router\interfaces\Router\Components\MatchingRouteInterface;
use FaustVik\Router\interfaces\Router\Components\RunnerInterface;
use FaustVik\Router\interfaces\Cache\CacheInterface;
use FaustVik\Router\Cache\CachedMatching;
use FaustVik\Router\Cache\FileCache;

final class Config implements ConfigInterface
{
    private RunnerInterface          $runner;
    private CheckHttpMethodInterface $checker;
    private MatchingRouteInterface   $match;
    private ?CacheInterface          $cache = null;
    private bool                     $cacheEnabled = false;
    private int                      $cacheTtl = 3600;

    public function __construct()
    {
        $this->runner  = new Runner();
        $this->checker = new CheckerHttpMethod();
        $this->match   = new CachedMatching(new Matching(), new FileCache());
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

    /**
     * @return MatchingRouteInterface
     */
    public function getMatch(): MatchingRouteInterface
    {
        return $this->match;
    }

    /**
     * @param MatchingRouteInterface $matcher
     */
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
}
