<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use FaustVik\Router\interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;

final class RoutesCollection implements RoutesCollectionInterface
{
    /** @var array<int, RouteInterface> */
    private array $collections = [];

    public function set(RouteInterface ...$routes): void
    {
        foreach ($routes as $route) {
            $this->collections[] = $route;
        }
    }

    /**
     * @return RouteInterface[]
     */
    public function get(): array
    {
        return $this->collections;
    }

    /** Helper методы для HTTP методов */
    /**
     * @param array<int, mixed> $arg
     */
    public function addGet(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['GET']);
        $this->set($routeObject);
        return $routeObject;
    }

    /**
     * @param array<int, mixed> $arg
     */
    public function addPost(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['POST']);
        $this->set($routeObject);
        return $routeObject;
    }

    /**
     * @param array<int, mixed> $arg
     */
    public function addPut(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['PUT']);
        $this->set($routeObject);
        return $routeObject;
    }

    /**
     * @param array<int, mixed> $arg
     */
    public function addDelete(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['DELETE']);
        $this->set($routeObject);
        return $routeObject;
    }

    /**
     * @param array<int, mixed> $arg
     */
    public function addPatch(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['PATCH']);
        $this->set($routeObject);
        return $routeObject;
    }

    /**
     * @param array<int, mixed> $arg
     */
    public function addAny(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create(
            $route,
            $class,
            $action,
            $arg,
            ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']
        );
        $this->set($routeObject);
        return $routeObject;
    }

    /**
     * @param array<int, string> $methods
     * @param array<int, mixed> $arg
     */
    public function addMatch(
        array $methods,
        string $route,
        string $class,
        string $action,
        array $arg = []
    ): RouteInterface {
        $routeObject = Route::create($route, $class, $action, $arg, $methods);
        $this->set($routeObject);
        return $routeObject;
    }

    /** Helper методы для анонимных функций */
    public function addGetFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['GET']);
        $this->set($routeObject);
        return $routeObject;
    }

    public function addPostFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['POST']);
        $this->set($routeObject);
        return $routeObject;
    }

    public function addPutFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['PUT']);
        $this->set($routeObject);
        return $routeObject;
    }

    public function addDeleteFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['DELETE']);
        $this->set($routeObject);
        return $routeObject;
    }

    public function addPatchFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['PATCH']);
        $this->set($routeObject);
        return $routeObject;
    }

    public function addAnyFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create(
            $route,
            $func,
            ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']
        );
        $this->set($routeObject);
        return $routeObject;
    }

    /**
     * @param array<int, string> $methods
     */
    public function addMatchFunc(array $methods, string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, $methods);
        $this->set($routeObject);
        return $routeObject;
    }

    /** Группировка */
    public function prefix(string $prefix): RouteGroup
    {
        $group = new RouteGroup($this);
        $group->prefix($prefix);
        return $group;
    }

    /**
     * @param array<int, string|callable> $middleware
     */
    public function middleware(array $middleware): RouteGroup
    {
        $group = new RouteGroup($this);
        $group->middleware($middleware);
        return $group;
    }

    public function group(callable $callback): void
    {
        $group = new RouteGroup($this);
        $callback($group);
    }
}
