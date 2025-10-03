<?php

declare(strict_types=1);

namespace FaustVik\Router\interfaces\Router\Components;

use FaustVik\Router\exceptions\NoMatch;
use FaustVik\Router\interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\Router\Components\matching\MatchResult;

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
