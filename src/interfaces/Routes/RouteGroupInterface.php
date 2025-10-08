<?php

declare(strict_types=1);

namespace FaustVik\Router\interfaces\Routes;

interface RouteGroupInterface
{
    public function prefix(string $prefix): self;

    /**
     * @param array<int, string|callable> $middleware
     */
    public function middleware(array $middleware): self;

    /**
     * @param array<int, mixed> $arg
     */
    public function get(string $route, string $class, string $action, array $arg = []): RouteInterface;

    /**
     * @param array<int, mixed> $arg
     */
    public function post(string $route, string $class, string $action, array $arg = []): RouteInterface;

    /**
     * @param array<int, mixed> $arg
     */
    public function put(string $route, string $class, string $action, array $arg = []): RouteInterface;

    /**
     * @param array<int, mixed> $arg
     */
    public function delete(string $route, string $class, string $action, array $arg = []): RouteInterface;

    /**
     * @param array<int, mixed> $arg
     */
    public function patch(string $route, string $class, string $action, array $arg = []): RouteInterface;

    /**
     * @param array<int, mixed> $arg
     */
    public function any(string $route, string $class, string $action, array $arg = []): RouteInterface;

    /**
     * @param array<int, string> $methods
     * @param array<int, mixed> $arg
     */
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

    /**
     * @param array<int, string> $methods
     */
    public function matchFunc(array $methods, string $route, callable $func): RouteInterface;

    /** Группировка */
    public function group(callable $callback): void;

    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array;
}
