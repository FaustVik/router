<?php

namespace FaustVik\Router\interfaces\Routes;

use FaustVik\Router\Validation\ParameterValidationRule;

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
     * Get validation rules for route parameters
     *
     * @return ParameterValidationRule[]
     */
    public function getValidationRules(): array;

    /**
     * Set validation rules for route parameters
     *
     * @param ParameterValidationRule[] $rules
     * @return self
     */
    public function validate(array $rules): self;
}
