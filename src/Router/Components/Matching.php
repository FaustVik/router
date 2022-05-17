<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components;

use FaustVik\Router\exceptions\NoMatch;
use FaustVik\Router\interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\interfaces\Router\Components\MatchingRouteInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;

final class Matching implements MatchingRouteInterface
{
    /**
     * @param string                    $uri
     * @param RoutesCollectionInterface $collections
     *
     * @return RouteInterface
     * @throws NoMatch
     */
    public function match(string $uri, RoutesCollectionInterface $collections): RouteInterface
    {
        foreach ($collections->get() as $route) {
            if (in_array($uri, [$route->alias(), $route->getRoute()], true)) {
                return $route;
            }
        }

        throw new NoMatch($uri);
    }
}
