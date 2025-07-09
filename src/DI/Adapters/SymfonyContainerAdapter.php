<?php

namespace FaustVik\Router\DI\Adapters;

use FaustVik\Router\interfaces\DI\RouterContainerInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Symfony Container Adapter
 * Adapts Symfony DI container to work with RouterContainerInterface
 */
class SymfonyContainerAdapter implements RouterContainerInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
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
        return $this->container->has($id) || class_exists($id);
    }    public function resolve(string $class, array $parameters = []): object
    {
        try {
            // Symfony container doesn't support parameters in get() method
            // We'll try to get the service or create it manually
            if ($this->container->has($class)) {
                return $this->container->get($class);
            }

            // Manual instantiation with reflection for non-registered services
            if (class_exists($class)) {
                return $this->createInstanceWithReflection($class, $parameters);
            }

            throw new \RuntimeException("Cannot resolve class: {$class}");
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            throw new \RuntimeException("Cannot resolve class: {$class}", 0, $e);
        }
    }    public function bind(string $abstract, mixed $concrete): void
    {
        // Symfony container is typically configured and compiled
        // Runtime binding is not supported in most Symfony containers
        throw new \RuntimeException("Runtime binding is not supported in Symfony container adapter. Configure services in container builder.");
    }    public function singleton(string $abstract, mixed $concrete): void
    {
        // Same as bind - not supported at runtime
        throw new \RuntimeException("Runtime binding is not supported in Symfony container adapter. Configure services in container builder.");
    }    public function bound(string $abstract): bool
    {
        return $this->container->has($abstract);
    }
    /**
     * Create instance using reflection
     */
    private function createInstanceWithReflection(string $class, array $parameters = []): object
    {
        $reflectionClass = new \ReflectionClass($class);

        if (!$reflectionClass->isInstantiable()) {
            throw new \RuntimeException("Class {$class} is not instantiable");
        }

        $constructor = $reflectionClass->getConstructor();

        if (!$constructor) {
            return $reflectionClass->newInstance();
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type && $type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();
                if ($this->container->has($typeName)) {
                    $dependencies[] = $this->container->get($typeName);
                } elseif (class_exists($typeName)) {
                    $dependencies[] = $this->createInstanceWithReflection($typeName);
                } else {
                    throw new \RuntimeException("Cannot resolve dependency: {$typeName}");
                }
            } else {
                // For built-in types, use provided parameters or default
                $dependencies[] = $parameters[$parameter->getName()] ??
                    ($parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null);
            }
        }

        return $reflectionClass->newInstanceArgs($dependencies);
    }
}
