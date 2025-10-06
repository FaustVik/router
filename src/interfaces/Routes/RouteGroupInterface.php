<?php

declare(strict_types=1);

namespace FaustVik\Router\interfaces\Routes;

interface RouteGroupInterface
{
    public function prefix(string $prefix): self;

    public function middleware(array $middleware): self;

    public function get(string $route, string $class, string $action, array $arg = []): RouteInterface;

    public function post(string $route, string $class, string $action, array $arg = []): RouteInterface;

    public function put(string $route, string $class, string $action, array $arg = []): RouteInterface;

    public function delete(string $route, string $class, string $action, array $arg = []): RouteInterface;

    public function patch(string $route, string $class, string $action, array $arg = []): RouteInterface;

    public function any(string $route, string $class, string $action, array $arg = []): RouteInterface;

    public function match(
        array $methods,
        string $route,
        string $class,
        string $action,
        array $arg = []
    ): RouteInterface;

    /** Методы для анонимных функций */
    public function getFunc(string $route, callable $func): RouteInterface;

    public function postFunc(string $route, callable $func): RouteInterface;

    public function putFunc(string $route, callable $func): RouteInterface;

    public function deleteFunc(string $route, callable $func): RouteInterface;

    public function patchFunc(string $route, callable $func): RouteInterface;

    public function anyFunc(string $route, callable $func): RouteInterface;

    public function matchFunc(array $methods, string $route, callable $func): RouteInterface;

    /** Группировка */
    public function group(callable $callback): void;

    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array;
}
