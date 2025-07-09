<?php
declare(strict_types=1);
namespace FaustVik\Router\DI\Adapters;

use DI\Container;
use FaustVik\Router\interfaces\DI\RouterContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * PHP-DI Container Adapter
 * Adapts PHP-DI container to work with RouterContainerInterface
 */
class PhpDiContainerAdapter implements RouterContainerInterface
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }    public function has(string $id): bool
    {
        return $this->container->has($id);
    }    public function canResolve(string $id): bool
    {
        // PHP-DI can resolve any class through reflection
        return $this->container->has($id) || class_exists($id);
    }    public function resolve(string $class, array $parameters = []): object
    {
        try {
            if (!empty($parameters)) {
                return $this->container->make($class, $parameters);
            }
            return $this->container->get($class);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            throw new \RuntimeException("Cannot resolve class: {$class}", 0, $e);
        }
    }    public function bind(string $abstract, mixed $concrete): void
    {
        $this->container->set($abstract, $concrete);
    }    public function singleton(string $abstract, mixed $concrete): void
    {
        // PHP-DI treats all bindings as singletons by default
        $this->container->set($abstract, $concrete);
    }    public function bound(string $abstract): bool
    {
        return $this->container->has($abstract);
    }
}
