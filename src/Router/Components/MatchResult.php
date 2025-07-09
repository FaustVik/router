<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components;

use FaustVik\Router\interfaces\Routes\RouteInterface;

final class MatchResult
{
    public function __construct(
        private RouteInterface $route,
        private array $parameters = []
    ) {
    }//end __construct()

    public function getRoute(): RouteInterface
    {
        return $this->route;
    }//end getRoute()

    public function getParameters(): array
    {
        return $this->parameters;
    }//end getParameters()
}//end class
