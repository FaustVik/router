<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components;

use FaustVik\Router\interfaces\Routes\RouteInterface;

final class MatchResult
{
    public function __construct(
        private RouteInterface $route,
        private array $parameters = []
    ) {}

    public function getRoute(): RouteInterface
    {
        return $this->route;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
} 