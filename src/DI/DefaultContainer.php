<?php

declare(strict_types=1);

namespace FaustVik\Router\DI;

use DI\Container;
use DI\ContainerBuilder;
use FaustVik\Router\interfaces\DI\RouterContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Default Container implementation using PHP-DI
 * Provides a simple API for basic dependency injection needs
 */
class DefaultContainer implements RouterContainerInterface
{
    private Container $container;
    /** @var array<string, mixed> */
    private array $bindings = [];
    /** @var array<string, mixed> */
    private array $singletons = [];

    public function __construct()
    {
        $this->container = new Container();
    }

    /**
     * Create container with configuration
     * 
     * @param array<string, mixed> $config
     */
    public static function withConfig(array $config = []): self
    {
        $instance = new self();

        // Apply configuration if provided
        if (isset($config['bindings'])) {
            foreach ($config['bindings'] as $abstract => $concrete) {
                $instance->bind($abstract, $concrete);
            }
        }

        if (isset($config['singletons'])) {
            foreach ($config['singletons'] as $abstract => $concrete) {
                $instance->singleton($abstract, $concrete);
            }
        }

        return $instance;
    }

    /**
     * Create container with closure configuration
     */
    public static function withClosure(\Closure $configurator): self
    {
        $instance = new self();
        $configurator($instance);
        return $instance;
    }

    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    public function canResolve(string $id): bool
    {
        return $this->container->has($id) || class_exists($id);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function resolve(string $class, array $parameters = []): object
    {
        try {
            if (!empty($parameters)) {
                return $this->container->make($class, $parameters);
            }
            return $this->container->get($class);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            throw new \RuntimeException("Cannot resolve class: {$class}", 0, $e);
        }
    }

    public function bind(string $abstract, mixed $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
        $this->container->set($abstract, $concrete);
    }

    public function singleton(string $abstract, mixed $concrete): void
    {
        $this->singletons[$abstract] = $concrete;
        // PHP-DI treats all bindings as singletons by default
        $this->container->set($abstract, $concrete);
    }

    public function bound(string $abstract): bool
    {
        return $this->container->has($abstract);
    }

    /**
     * Get all bindings
     * 
     * @return array<string, mixed>
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Get all singletons
     * 
     * @return array<string, mixed>
     */
    public function getSingletons(): array
    {
        return $this->singletons;
    }

    /**
     * Clear all bindings and singletons
     */
    public function clear(): void
    {
        $this->bindings = [];
        $this->singletons = [];
        $this->container = new Container();
    }

    /**
     * Get the underlying PHP-DI container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Set a factory for creating instances
     */
    public function factory(string $abstract, \Closure $factory): void
    {
        $this->bind($abstract, $factory);
    }

    /**
     * Bind an interface to implementation
     */
    public function bindInterface(string $interface, string $implementation): void
    {
        $this->bind($interface, $implementation);
    }

    /**
     * Register a singleton factory
     */
    public function singletonFactory(string $abstract, \Closure $factory): void
    {
        $this->singleton($abstract, $factory);
    }
}
