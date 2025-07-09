<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use FaustVik\Router\interfaces\Routes\RouteGroupInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;
use FaustVik\Router\Validation\ParameterValidationRule;

final class RouteGroup implements RouteGroupInterface
{
    private string $prefix = '';
    private array $middleware = [];
    private array $routes = [];
    private array $validationRules = [];

    public function __construct(
        private readonly RoutesCollection $collection
    ) {
    }//end __construct()

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
    }//end prefix()

    public function middleware(array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }//end middleware()

    public function validate(array $rules): self
    {
        $this->validationRules = array_merge($this->validationRules, $rules);
        return $this;
    }//end validate()

    public function get(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = Route::create($fullRoute, $class, $action, $arg, ['GET']);
        $routeObject->middleware($this->middleware);
        $routeObject->validate($this->validationRules);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }//end get()

    public function post(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = Route::create($fullRoute, $class, $action, $arg, ['POST']);
        $routeObject->middleware($this->middleware);
        $routeObject->validate($this->validationRules);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }//end post()

    public function put(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = Route::create($fullRoute, $class, $action, $arg, ['PUT']);
        $routeObject->middleware($this->middleware);
        $routeObject->validate($this->validationRules);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }//end put()

    public function delete(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = Route::create($fullRoute, $class, $action, $arg, ['DELETE']);
        $routeObject->middleware($this->middleware);
        $routeObject->validate($this->validationRules);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }//end delete()

    public function patch(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = Route::create($fullRoute, $class, $action, $arg, ['PATCH']);
        $routeObject->middleware($this->middleware);
        $routeObject->validate($this->validationRules);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }//end patch()

    public function any(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = Route::create($fullRoute, $class, $action, $arg, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']);
        $routeObject->middleware($this->middleware);
        $routeObject->validate($this->validationRules);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }//end any()

    public function match(array $methods, string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = Route::create($fullRoute, $class, $action, $arg, $methods);
        $routeObject->middleware($this->middleware);
        $routeObject->validate($this->validationRules);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }//end match()

    public function getFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['GET']);
        $routeObject->middleware($this->middleware);
        $routeObject->validate($this->validationRules);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }//end getFunc()

    public function postFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['POST']);
        $routeObject->middleware($this->middleware);
        $routeObject->validate($this->validationRules);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }//end postFunc()

    public function putFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['PUT']);
        $routeObject->middleware($this->middleware);
        $routeObject->validate($this->validationRules);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }//end putFunc()

    public function deleteFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['DELETE']);
        $routeObject->middleware($this->middleware);
        $routeObject->validate($this->validationRules);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }//end deleteFunc()

    public function patchFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['PATCH']);
        $routeObject->middleware($this->middleware);
        $routeObject->validate($this->validationRules);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }//end patchFunc()

    public function anyFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']);
        $routeObject->middleware($this->middleware);
        $routeObject->validate($this->validationRules);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }//end anyFunc()

    public function matchFunc(array $methods, string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, $methods);
        $routeObject->middleware($this->middleware);
        $routeObject->validate($this->validationRules);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }//end matchFunc()

    public function group(callable $callback): void
    {
        $nestedGroup = new self($this->collection);
        // Копируем текущее состояние для вложенной группы
        $nestedGroup->prefix = $this->prefix;
        $nestedGroup->middleware = array_merge([], $this->middleware);
        $nestedGroup->validationRules = array_merge([], $this->validationRules);

        $callback($nestedGroup);

        // Добавляем все маршруты из вложенной группы
        foreach ($nestedGroup->getRoutes() as $route) {
            $this->routes[] = $route;
        }
    }//end group()

    public function getRoutes(): array
    {
        return $this->routes;
    }//end getRoutes()
}//end class
