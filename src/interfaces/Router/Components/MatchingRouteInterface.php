<?php

namespace FaustVik\Router\interfaces\Router\Components;

use FaustVik\Router\exceptions\NoMatch;
use FaustVik\Router\interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\Router\Components\MatchResult;

interface MatchingRouteInterface
{
    /**
     * @param string                    $uri
     * @param RoutesCollectionInterface $collections
     *
     * @return MatchResult
     * @throws NoMatch
     */
    public function match(string $uri, RoutesCollectionInterface $collections): MatchResult;
}
