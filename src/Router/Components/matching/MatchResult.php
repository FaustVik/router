<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components\matching;

use FaustVik\Router\interfaces\Routes\RouteInterface;

final class MatchResult
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        private RouteInterface $route,
        private array $parameters = []
    ) {
    }

    public function getRoute(): RouteInterface
    {
        return $this->route;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
