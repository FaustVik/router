<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use FaustVik\Router\interfaces\Routes\RouteGroupInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;

final class RouteGroup implements RouteGroupInterface
{
    private string $prefix = '';
    private array $middleware = [];
    private array $routes = [];

    public function __construct(
        private readonly RoutesCollection $collection
    ) {}

    public function prefix(string $prefix): self
    {
        // Если уже есть префикс, добавляем к нему
        $newPrefix = rtrim($prefix, '/');
        if ($this->prefix !== '') {
            $this->prefix = $this->prefix . $newPrefix;
        } else {
            $this->prefix = $newPrefix;
        }
        return $this;
    }

    public function middleware(array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    public function get(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        return $this->addRoute(['GET'], $route, $class, $action, $arg);
    }

    public function post(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        return $this->addRoute(['POST'], $route, $class, $action, $arg);
    }

    public function put(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        return $this->addRoute(['PUT'], $route, $class, $action, $arg);
    }

    public function delete(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        return $this->addRoute(['DELETE'], $route, $class, $action, $arg);
    }

    public function patch(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        return $this->addRoute(['PATCH'], $route, $class, $action, $arg);
    }

    public function any(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $route, $class, $action, $arg);
    }

    public function match(array $methods, string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        return $this->addRoute($methods, $route, $class, $action, $arg);
    }

    // Методы для анонимных функций
    public function getFunc(string $route, callable $func): RouteInterface
    {
        return $this->addFuncRoute(['GET'], $route, $func);
    }

    public function postFunc(string $route, callable $func): RouteInterface
    {
        return $this->addFuncRoute(['POST'], $route, $func);
    }

    public function putFunc(string $route, callable $func): RouteInterface
    {
        return $this->addFuncRoute(['PUT'], $route, $func);
    }

    public function deleteFunc(string $route, callable $func): RouteInterface
    {
        return $this->addFuncRoute(['DELETE'], $route, $func);
    }

    public function patchFunc(string $route, callable $func): RouteInterface
    {
        return $this->addFuncRoute(['PATCH'], $route, $func);
    }

    public function anyFunc(string $route, callable $func): RouteInterface
    {
        return $this->addFuncRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $route, $func);
    }

    public function matchFunc(array $methods, string $route, callable $func): RouteInterface
    {
        return $this->addFuncRoute($methods, $route, $func);
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

    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    private function addRoute(array $methods, string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        // Добавляем префикс к маршруту
        $fullRoute = $this->prefix . '/' . ltrim($route, '/');
        $fullRoute = rtrim($fullRoute, '/') ?: '/';

        // Создаем маршрут
        $routeObject = Route::create($fullRoute, $class, $action, $arg, $methods);

        // Применяем middleware группы
        if (!empty($this->middleware)) {
            $routeObject->middleware($this->middleware);
        }

        // Добавляем в коллекцию и локальный массив
        $this->routes[] = $routeObject;
        $this->collection->set($routeObject);

        return $routeObject;
    }

    private function addFuncRoute(array $methods, string $route, callable $func): RouteInterface
    {
        // Добавляем префикс к маршруту
        $fullRoute = $this->prefix . '/' . ltrim($route, '/');
        $fullRoute = rtrim($fullRoute, '/') ?: '/';

        // Создаем маршрут с анонимной функцией
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, $methods);

        // Применяем middleware группы
        if (!empty($this->middleware)) {
            $routeObject->middleware($this->middleware);
        }

        // Добавляем в коллекцию и локальный массив
        $this->routes[] = $routeObject;
        $this->collection->set($routeObject);

        return $routeObject;
    }
} 