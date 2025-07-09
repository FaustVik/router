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

    /**
     * Get middleware for route
     *
     * @return array
     */
    public function getMiddleware(): array;

    /**
     * Set middleware for route
     *
     * @param array $middleware
     * @return self
     */
    public function middleware(array $middleware): self;
}
