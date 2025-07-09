<?php
declare(strict_types=1);
namespace FaustVik\Router\DI\Adapters;

use FaustVik\Router\interfaces\DI\RouterContainerInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Pimple Container Adapter
 * Adapts Pimple container to work with RouterContainerInterface
 */
class PimpleContainerAdapter implements RouterContainerInterface
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
        // Pimple doesn't support runtime binding through PSR-11 interface
        throw new \RuntimeException("Runtime binding is not supported in Pimple container adapter. Use Pimple native API or configure services before creating adapter.");
    }    public function singleton(string $abstract, mixed $concrete): void
    {
        // Same as bind - not supported at runtime
        throw new \RuntimeException("Runtime binding is not supported in Pimple container adapter. Use Pimple native API or configure services before creating adapter.");
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
