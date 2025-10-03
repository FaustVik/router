<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use FaustVik\Router\interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;

final class RoutesCollection implements RoutesCollectionInterface
{
    /**@var RouteInterface[] $collections */
    private array $collections = [];
    private array $globalValidationRules = [];

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

    public function validate(array $rules): self
    {
        $this->globalValidationRules = array_merge($this->globalValidationRules, $rules);
        return $this;
    }

    public function getGlobalValidationRules(): array
    {
        return $this->globalValidationRules;
    }

    /** Helper методы для HTTP методов */
    public function addGet(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['GET']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }

    public function addPost(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['POST']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }

    public function addPut(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['PUT']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }

    public function addDelete(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['DELETE']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }

    public function addPatch(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['PATCH']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }

    public function addAny(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create(
            $route,
            $class,
            $action,
            $arg,
            ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']
        );
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }

    public function addMatch(
        array $methods,
        string $route,
        string $class,
        string $action,
        array $arg = []
    ): RouteInterface {
        $routeObject = Route::create($route, $class, $action, $arg, $methods);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }

    /** Helper методы для анонимных функций */
    public function addGetFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['GET']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }

    public function addPostFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['POST']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }

    public function addPutFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['PUT']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }

    public function addDeleteFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['DELETE']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }

    public function addPatchFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['PATCH']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
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
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }

    public function addMatchFunc(array $methods, string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, $methods);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
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
