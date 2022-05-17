<?php

namespace FaustVik\Router\test\Com;

use FaustVik\Router\exceptions\NoMatch;
use FaustVik\Router\interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\interfaces\Router\Components\MatchingRouteInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;

class Matcher implements MatchingRouteInterface
{
    /**
     * @inheritDoc
     */
    public function match(string $uri, RoutesCollectionInterface $collections): RouteInterface
    {
        $uri =str_replace('//', '/', $uri);

        foreach ($collections->get() as $route) {
            if (in_array($uri, [$route->alias(), $route->getRoute()], true)) {
                return $route;
            }
        }

        throw new NoMatch($uri);
    }
}
