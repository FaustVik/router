<?php

declare(strict_types=1);

namespace FaustVik\Router\Cache;

use FaustVik\Router\interfaces\Cache\CacheInterface;
use FaustVik\Router\interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\interfaces\Router\Components\MatchingRouteInterface;
use FaustVik\Router\interfaces\Routes\RouteAnonymousFuncInterface;
use FaustVik\Router\interfaces\Routes\RouteClassInterface;
use FaustVik\Router\Router\Components\matching\Matching;
use FaustVik\Router\Router\Components\matching\MatchResult;

/**
 * Cached route matching implementation
 *
 * Wraps route matching logic with caching layer to improve performance.
 * Caches matching results to avoid repeated regex matching on same URIs.
 *
 * @package FaustVik\Router\Cache
 */
final class CachedMatching implements MatchingRouteInterface
{
    private MatchingRouteInterface $originalMatcher;
    private CacheInterface $cache;
    private bool $cacheEnabled = false;
    private int $cacheTtl = 3600; // 1 hour by default

    public function __construct(?MatchingRouteInterface $matcher = null, ?CacheInterface $cache = null)
    {
        $this->originalMatcher = $matcher ?? new Matching();
        $this->cache = $cache ?? new FileCache();
    }

    public function match(string $uri, ?RoutesCollectionInterface $collection): MatchResult
    {
        if (!$this->cacheEnabled || $collection === null) {
            return $this->originalMatcher->match($uri, $collection);
        }

        $cacheKey = $this->generateCacheKey($uri, $collection);

        // Пытаемся получить результат из кеша
        $cachedResult = $this->cache->get($cacheKey);
        if ($cachedResult instanceof MatchResult) {
            return $cachedResult;
        }

        // Если в кеше нет, выполняем обычный матчинг
        $result = $this->originalMatcher->match($uri, $collection);

        // Сохраняем результат в кеш
        $this->cache->set($cacheKey, $result, $this->cacheTtl);

        return $result;
    }

    public function enableCache(): void
    {
        $this->cacheEnabled = true;
    }

    public function disableCache(): void
    {
        $this->cacheEnabled = false;
    }

    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function setCacheTtl(int $ttl): void
    {
        $this->cacheTtl = $ttl;
    }

    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    public function clearCache(): bool
    {
        return $this->cache->clear();
    }

    private function generateCacheKey(string $uri, RoutesCollectionInterface $collection): string
    {
        $routesHash = $this->generateRoutesHash($collection);
        return 'route_match_' . md5($uri . '_' . $routesHash);
    }

    private function generateRoutesHash(RoutesCollectionInterface $collection): string
    {
        $routes = $collection->get();
        $routesData = [];

        foreach ($routes as $route) {
            $routeData = [
                'pattern' => $route->getRoute(),
                'methods' => $route->getMethods(),
                'middleware' => $route->getMiddleware()
            ];

            // Добавляем специфичные для RouteClassInterface данные
            if ($route instanceof RouteClassInterface) {
                $routeData['class'] = $route->getClass();
                $routeData['action'] = $route->getAction();
            }

            // Добавляем специфичные для RouteAnonymousFuncInterface данные
            if ($route instanceof RouteAnonymousFuncInterface) {
                $routeData['func_hash'] = spl_object_hash($route->getFunc());
            }

            $routesData[] = $routeData;
        }

        return md5(serialize($routesData));
    }
}
