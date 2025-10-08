<?php

declare(strict_types=1);

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

    /**
     * Get route name
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Set route name
     *
     * @param string $name
     * @return self
     */
    public function name(string $name): self;

    /**
     * Get constraints for route parameters
     *
     * @return array
     */
    public function getConstraints(): array;

    /**
     * Add constraint for route parameter
     *
     * @param string $param Parameter name
     * @param string $pattern Regular expression pattern
     * @return self
     */
    public function where(string $param, string $pattern): self;
}
