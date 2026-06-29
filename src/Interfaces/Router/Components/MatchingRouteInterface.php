<?php

declare(strict_types=1);

namespace FaustVik\Router\Interfaces\Router\Components;

use FaustVik\Router\Exceptions\NoMatch;
use FaustVik\Router\Interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\Router\Components\matching\MatchResult;

interface MatchingRouteInterface
{
    /**
     * @param string                         $uri
     * @param RoutesCollectionInterface|null $collections
     *
     * @return MatchResult
     * @throws NoMatch
     */
    public function match(string $uri, ?RoutesCollectionInterface $collections): MatchResult;
}
