<?php

namespace FaustVik\Router\interfaces\DI;

use Psr\Container\ContainerInterface;

/**
 * Interface for router-specific container functionality
 * Extends PSR-11 ContainerInterface with additional methods for auto-wiring
 */
interface RouterContainerInterface extends ContainerInterface
{
    /**
     * Check if the container can resolve a given identifier
     *
     * @param string $id Service identifier
     * @return bool
     */
    public function canResolve(string $id): bool;

    /**
     * Resolve a class with its dependencies
     *
     * @param string $class Class name to resolve
     * @param array $parameters Additional parameters to pass to constructor
     * @return object
     */
    public function resolve(string $class, array $parameters = []): object;

    /**
     * Bind a service to the container
     *
     * @param string $abstract Service identifier
     * @param mixed $concrete Implementation (class name, closure, or instance)
     * @return void
     */
    public function bind(string $abstract, mixed $concrete): void;

    /**
     * Bind a service as singleton
     *
     * @param string $abstract Service identifier
     * @param mixed $concrete Implementation (class name, closure, or instance)
     * @return void
     */
    public function singleton(string $abstract, mixed $concrete): void;

    /**
     * Check if a service is bound
     *
     * @param string $abstract Service identifier
     * @return bool
     */
    public function bound(string $abstract): bool;
} 