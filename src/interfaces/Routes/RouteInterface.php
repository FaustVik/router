<?php

namespace FaustVik\Router\interfaces\Routes;

interface RouteInterface
{
    /**
     * Get route
     *
     * @return string
     */
    public function getRoute(): string;

    /**
     * Get allowed http methods for route
     *
     * @return array
     */
    public function getMethods(): array;

    /**
     * Alias for route
     *
     * @return string|null
     */
    public function alias(): ?string;
}
