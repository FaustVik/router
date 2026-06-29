<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components\matching;

use FaustVik\Router\Interfaces\Routes\RouteInterface;

/**
 * Route matching result
 *
 * Contains matched route and extracted URL parameters.
 *
 * @package FaustVik\Router\Router\Components\matching
 */
final class MatchResult
{
    /**
     * @param RouteInterface $route Matched route
     * @param array<string, mixed> $parameters Extracted URL parameters
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
