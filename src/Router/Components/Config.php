<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components;

use FaustVik\Router\interfaces\Router\Components\CheckHttpMethodInterface;
use FaustVik\Router\interfaces\Router\Components\ConfigInterface;
use FaustVik\Router\interfaces\Router\Components\MatchingRouteInterface;
use FaustVik\Router\interfaces\Router\Components\RunnerInterface;
use FaustVik\Router\interfaces\Cache\CacheInterface;
use FaustVik\Router\interfaces\DI\RouterContainerInterface;
use FaustVik\Router\Cache\CachedMatching;
use FaustVik\Router\Cache\FileCache;

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
        $this->match   = new CachedMatching(new Matching(), new FileCache());
    }//end __construct()

    public function setRunner(RunnerInterface $runner): void
    {
        $this->runner = $runner;
    }//end setRunner()

    public function getRunner(): RunnerInterface
    {
        return $this->runner;
    }//end getRunner()

    public function setCheckerHttpMethod(CheckHttpMethodInterface $checker): void
    {
        $this->checker = $checker;
    }//end setCheckerHttpMethod()

    public function getCheckerHttpMethod(): CheckHttpMethodInterface
    {
        return $this->checker;
    }//end getCheckerHttpMethod()

    public function getMatch(): MatchingRouteInterface
    {
        return $this->match;
    }//end getMatch()

    public function setMatcher(MatchingRouteInterface $matcher): void
    {
        $this->match = $matcher;
    }//end setMatcher()

    public function enableCache(): void
    {
        $this->cacheEnabled = true;
        if ($this->match instanceof CachedMatching) {
            $this->match->enableCache();
        }
    }//end enableCache()

    public function disableCache(): void
    {
        $this->cacheEnabled = false;
        if ($this->match instanceof CachedMatching) {
            $this->match->disableCache();
        }
    }//end disableCache()

    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }//end isCacheEnabled()

    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
        if ($this->match instanceof CachedMatching) {
            $this->match->setCache($cache);
        }
    }//end setCache()

    public function getCache(): ?CacheInterface
    {
        return $this->cache;
    }//end getCache()

    public function setCacheTtl(int $ttl): void
    {
        $this->cacheTtl = $ttl;
        if ($this->match instanceof CachedMatching) {
            $this->match->setCacheTtl($ttl);
        }
    }//end setCacheTtl()

    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }//end getCacheTtl()

    public function clearCache(): bool
    {
        if ($this->match instanceof CachedMatching) {
            return $this->match->clearCache();
        }
        return false;
    }//end clearCache()

    public function setContainer(?RouterContainerInterface $container): void
    {
        $this->container = $container;

        // Update runner with container
        if ($this->runner instanceof Runner) {
            $this->runner->setContainer($container);
        }
    }//end setContainer()

    public function getContainer(): ?RouterContainerInterface
    {
        return $this->container;
    }//end getContainer()
}//end class
