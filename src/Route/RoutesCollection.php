<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use FaustVik\Router\interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;
use FaustVik\Router\Validation\ParameterValidationRule;

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
    }//end set()

    /**
     * @return RouteInterface[]
     */
    public function get(): array
    {
        return $this->collections;
    }//end get()

    public function validate(array $rules): self
    {
        $this->globalValidationRules = array_merge($this->globalValidationRules, $rules);
        return $this;
    }//end validate()

    public function getGlobalValidationRules(): array
    {
        return $this->globalValidationRules;
    }//end getGlobalValidationRules()

    // Helper методы для HTTP методов
    public function addGet(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['GET']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }//end addGet()

    public function addPost(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['POST']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }//end addPost()

    public function addPut(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['PUT']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }//end addPut()

    public function addDelete(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['DELETE']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }//end addDelete()

    public function addPatch(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['PATCH']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }//end addPatch()

    public function addAny(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }//end addAny()

    public function addMatch(array $methods, string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, $methods);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }//end addMatch()

    // Helper методы для анонимных функций
    public function addGetFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['GET']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }//end addGetFunc()

    public function addPostFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['POST']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }//end addPostFunc()

    public function addPutFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['PUT']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }//end addPutFunc()

    public function addDeleteFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['DELETE']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }//end addDeleteFunc()

    public function addPatchFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['PATCH']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }//end addPatchFunc()

    public function addAnyFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }//end addAnyFunc()

    public function addMatchFunc(array $methods, string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, $methods);
        if (!empty($this->globalValidationRules)) {
            $routeObject->validate($this->globalValidationRules);
        }
        $this->set($routeObject);
        return $routeObject;
    }//end addMatchFunc()

    // Группировка
    public function prefix(string $prefix): RouteGroup
    {
        $group = new RouteGroup($this);
        $group->prefix($prefix);
        return $group;
    }//end prefix()

    public function middleware(array $middleware): RouteGroup
    {
        $group = new RouteGroup($this);
        $group->middleware($middleware);
        return $group;
    }//end middleware()

    public function group(callable $callback): void
    {
        $group = new RouteGroup($this);
        $callback($group);
    }//end group()
}//end class
