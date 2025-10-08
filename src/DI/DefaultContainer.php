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
 *
 * Provides a simple API for basic dependency injection needs.
 * Built on top of PHP-DI for powerful auto-wiring capabilities.
 *
 * @package FaustVik\Router\DI
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
     * Creates container with configuration array
     *
     * @param array<string, mixed> $config Configuration array with 'bindings' and 'singletons'
     * @return self New container instance
     *
     * @example
     * $container = DefaultContainer::withConfig([
     *     'bindings' => [
     *         LoggerInterface::class => FileLogger::class
     *     ],
     *     'singletons' => [
     *         DatabaseConnection::class => DatabaseConnection::class
     *     ]
     * ]);
     */
    public static function withConfig(array $config = []): self
    {
        $instance = new self();

        // Apply configuration if provided
        if (isset($config['bindings']) && is_array($config['bindings'])) {
            foreach ($config['bindings'] as $abstract => $concrete) {
                if (!is_string($abstract)) {
                    throw new \InvalidArgumentException('Binding key must be a string');
                }
                $instance->bind($abstract, $concrete);
            }
        }

        if (isset($config['singletons']) && is_array($config['singletons'])) {
            foreach ($config['singletons'] as $abstract => $concrete) {
                if (!is_string($abstract)) {
                    throw new \InvalidArgumentException('Singleton key must be a string');
                }
                $instance->singleton($abstract, $concrete);
            }
        }

        return $instance;
    }

    /**
     * Creates container with closure configuration
     *
     * @param \Closure $configurator Function to configure the container
     * @return self New container instance
     *
     * @example
     * $container = DefaultContainer::withClosure(function($c) {
     *     $c->bind(LoggerInterface::class, FileLogger::class);
     *     $c->singleton(CacheInterface::class, RedisCache::class);
     * });
     */
    public static function withClosure(\Closure $configurator): self
    {
        $instance = new self();
        $configurator($instance);
        return $instance;
    }

    /**
     * Gets an entry from the container
     *
     * @param string $id Entry identifier
     * @return mixed Entry value
     *
     * @example
     * $logger = $container->get(LoggerInterface::class);
     */
    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    /**
     * Checks if container has an entry
     *
     * @param string $id Entry identifier
     * @return bool True if entry exists
     *
     * @example
     * if ($container->has(LoggerInterface::class)) {
     *     $logger = $container->get(LoggerInterface::class);
     * }
     */
    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * Checks if container can resolve a class
     *
     * @param string $id Class name or identifier
     * @return bool True if can be resolved
     *
     * @example
     * if ($container->canResolve(UserService::class)) {
     *     $service = $container->resolve(UserService::class);
     * }
     */
    public function canResolve(string $id): bool
    {
        return $this->container->has($id) || class_exists($id);
    }

    /**
     * Resolves a class with optional parameters
     *
     * @param string $class Class name to resolve
     * @param array<string, mixed> $parameters Additional parameters
     * @return object Resolved class instance
     *
     * @example
     * $service = $container->resolve(UserService::class);
     *
     * @example
     * // With parameters
     * $service = $container->resolve(EmailService::class, ['config' => $config]);
     */
    public function resolve(string $class, array $parameters = []): object
    {
        try {
            if (!empty($parameters)) {
                $result = $this->container->make($class, $parameters);
            } else {
                $result = $this->container->get($class);
            }
            
            if (!is_object($result)) {
                throw new \RuntimeException("Resolved value for '{$class}' is not an object");
            }
            
            return $result;
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            throw new \RuntimeException("Cannot resolve class: {$class}", 0, $e);
        }
    }

    /**
     * Binds an abstract type to a concrete implementation
     *
     * Creates a new instance each time it's resolved.
     *
     * @param string $abstract Abstract type (interface or class name)
     * @param mixed $concrete Concrete implementation (class name, closure, or instance)
     *
     * @example
     * $container->bind(LoggerInterface::class, FileLogger::class);
     *
     * @example
     * // With closure
     * $container->bind('cache', fn() => new RedisCache($config));
     */
    public function bind(string $abstract, mixed $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
        $this->container->set($abstract, $concrete);
    }

    /**
     * Binds an abstract type as singleton
     *
     * Returns the same instance each time it's resolved.
     *
     * @param string $abstract Abstract type (interface or class name)
     * @param mixed $concrete Concrete implementation (class name, closure, or instance)
     *
     * @example
     * $container->singleton(DatabaseConnection::class, DatabaseConnection::class);
     *
     * @example
     * // With closure
     * $container->singleton('db', fn() => new PDO('mysql:...'));
     */
    public function singleton(string $abstract, mixed $concrete): void
    {
        $this->singletons[$abstract] = $concrete;
        // PHP-DI treats all bindings as singletons by default
        $this->container->set($abstract, $concrete);
    }

    /**
     * Checks if abstract type is bound
     *
     * @param string $abstract Abstract type to check
     * @return bool True if bound
     *
     * @example
     * if ($container->bound(LoggerInterface::class)) {
     *     // Logger is configured
     * }
     */
    public function bound(string $abstract): bool
    {
        return $this->container->has($abstract);
    }

    /**
     * Gets all bindings
     *
     * @return array<string, mixed> All registered bindings
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Gets all singletons
     *
     * @return array<string, mixed> All registered singletons
     */
    public function getSingletons(): array
    {
        return $this->singletons;
    }

    /**
     * Clears all bindings and singletons
     *
     * @example
     * // In tests
     * $container->clear();
     */
    public function clear(): void
    {
        $this->bindings = [];
        $this->singletons = [];
        $this->container = new Container();
    }

    /**
     * Gets the underlying PHP-DI container
     *
     * @return Container PHP-DI container instance
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Sets a factory for creating instances
     *
     * @param string $abstract Abstract type
     * @param \Closure $factory Factory function
     *
     * @example
     * $container->factory(UserService::class, fn() => new UserService($db));
     */
    public function factory(string $abstract, \Closure $factory): void
    {
        $this->bind($abstract, $factory);
    }

    /**
     * Binds an interface to implementation
     *
     * @param string $interface Interface name
     * @param string $implementation Implementation class name
     *
     * @example
     * $container->bindInterface(CacheInterface::class, RedisCache::class);
     */
    public function bindInterface(string $interface, string $implementation): void
    {
        $this->bind($interface, $implementation);
    }

    /**
     * Registers a singleton factory
     *
     * @param string $abstract Abstract type
     * @param \Closure $factory Factory function
     *
     * @example
     * $container->singletonFactory('db', fn() => new PDO('mysql:...'));
     */
    public function singletonFactory(string $abstract, \Closure $factory): void
    {
        $this->singleton($abstract, $factory);
    }
}
