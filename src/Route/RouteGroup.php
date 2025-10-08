<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use FaustVik\Router\interfaces\Routes\RouteGroupInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;

final class RouteGroup implements RouteGroupInterface
{
    private string $prefix = '';
    /** @var array<int, string|callable> */
    private array $middleware = [];
    /** @var array<int, RouteInterface> */
    private array $routes = [];

    public function __construct(
        private readonly RoutesCollection $collection
    ) {
    }

    public function prefix(string $prefix): self
    {
        // Если уже есть префикс, добавляем к нему
        $newPrefix = rtrim($prefix, '/');
        if ($this->prefix !== '') {
            $this->prefix .= $newPrefix;
        } else {
            $this->prefix = $newPrefix;
        }
        return $this;
    }

    /**
     * @param array<int, string|callable> $middleware
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    /**
     * @param array<int, mixed> $arg
     */
    public function get(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = Route::create($fullRoute, $class, $action, $arg, ['GET']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    /**
     * @param array<int, mixed> $arg
     */
    public function post(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = Route::create($fullRoute, $class, $action, $arg, ['POST']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    /**
     * @param array<int, mixed> $arg
     */
    public function put(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = Route::create($fullRoute, $class, $action, $arg, ['PUT']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    /**
     * @param array<int, mixed> $arg
     */
    public function delete(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = Route::create($fullRoute, $class, $action, $arg, ['DELETE']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    /**
     * @param array<int, mixed> $arg
     */
    public function patch(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = Route::create($fullRoute, $class, $action, $arg, ['PATCH']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    /**
     * @param array<int, mixed> $arg
     */
    public function any(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = Route::create($fullRoute, $class, $action, $arg, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    /**
     * @param array<int, string> $methods
     * @param array<int, mixed> $arg
     */
    public function match(array $methods, string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = Route::create($fullRoute, $class, $action, $arg, $methods);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    public function getFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['GET']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    public function postFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['POST']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    public function putFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['PUT']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    public function deleteFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['DELETE']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    public function patchFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['PATCH']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    public function anyFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    /**
     * @param array<int, string> $methods
     */
    public function matchFunc(array $methods, string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, $methods);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    public function group(callable $callback): void
    {
        $nestedGroup = new self($this->collection);
        // Копируем текущее состояние для вложенной группы
        $nestedGroup->prefix = $this->prefix;
        $nestedGroup->middleware = array_merge([], $this->middleware);

        $callback($nestedGroup);

        // Добавляем все маршруты из вложенной группы
        foreach ($nestedGroup->getRoutes() as $route) {
            $this->routes[] = $route;
        }
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
