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
     * @return array<int, string>
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
     * @return array<int, string|callable>
     */
    public function getMiddleware(): array;

    /**
     * Set middleware for route
     *
     * @param array<int, string|callable> $middleware
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
     * @return array<string, string>
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
