<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use FaustVik\Router\Interfaces\Routes\RouteGroupInterface;
use FaustVik\Router\Interfaces\Routes\RouteInterface;

/**
 * Route group for organizing routes
 *
 * Allows grouping routes with common prefix and middleware.
 * Supports nested groups and fluent interface.
 *
 * @package FaustVik\Router\Route
 */
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

    /**
     * Sets prefix for all routes in the group
     *
     * @param string $prefix URL prefix (e.g. '/api', '/admin')
     * @return self For fluent interface
     *
     * @example
     * $group->prefix('/api');  // All routes will start with /api
     */
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
     * Sets middleware for all routes in the group
     *
     * @param array<int, string|callable> $middleware Array of middleware
     * @return self For fluent interface
     *
     * @example
     * $group->middleware([AuthMiddleware::class, AdminMiddleware::class]);
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    /**
     * Adds GET route to the group
     *
     * @param string $route URI pattern
     * @param string $class Controller class
     * @param string $action Controller method
     * @param array<int, mixed> $arg Additional arguments
     * @return RouteInterface
     *
     * @example
     * $group->get('/users', UserController::class, 'index');
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
     * Adds POST route to the group
     *
     * @param string $route URI pattern
     * @param string $class Controller class
     * @param string $action Controller method
     * @param array<int, mixed> $arg Additional arguments
     * @return RouteInterface
     *
     * @example
     * $group->post('/users', UserController::class, 'store');
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
     * Adds PUT route to the group
     *
     * @param string $route URI pattern
     * @param string $class Controller class
     * @param string $action Controller method
     * @param array<int, mixed> $arg Additional arguments
     * @return RouteInterface
     *
     * @example
     * $group->put('/users/{id}', UserController::class, 'update');
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
     * Adds DELETE route to the group
     *
     * @param string $route URI pattern
     * @param string $class Controller class
     * @param string $action Controller method
     * @param array<int, mixed> $arg Additional arguments
     * @return RouteInterface
     *
     * @example
     * $group->delete('/users/{id}', UserController::class, 'destroy');
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
     * Adds PATCH route to the group
     *
     * @param string $route URI pattern
     * @param string $class Controller class
     * @param string $action Controller method
     * @param array<int, mixed> $arg Additional arguments
     * @return RouteInterface
     *
     * @example
     * $group->patch('/users/{id}', UserController::class, 'patch');
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
     * Adds route for any HTTP method
     *
     * @param string $route URI pattern
     * @param string $class Controller class
     * @param string $action Controller method
     * @param array<int, mixed> $arg Additional arguments
     * @return RouteInterface
     *
     * @example
     * $group->any('/webhook', WebhookController::class, 'handle');
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
     * Adds route for specific HTTP methods
     *
     * @param array<int, string> $methods Array of HTTP methods
     * @param string $route URI pattern
     * @param string $class Controller class
     * @param string $action Controller method
     * @param array<int, mixed> $arg Additional arguments
     * @return RouteInterface
     *
     * @example
     * $group->match(['GET', 'POST'], '/form', FormController::class, 'handle');
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

    /**
     * Adds GET route with closure handler
     *
     * @param string $route URI pattern
     * @param callable $func Handler function
     * @return RouteInterface
     *
     * @example
     * $group->getFunc('/hello', fn() => "Hello World!");
     */
    public function getFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['GET']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    /**
     * Adds POST route with closure handler
     *
     * @param string $route URI pattern
     * @param callable $func Handler function
     * @return RouteInterface
     *
     * @example
     * $group->postFunc('/submit', fn() => "Processing...");
     */
    public function postFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['POST']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    /**
     * Adds PUT route with closure handler
     *
     * @param string $route URI pattern
     * @param callable $func Handler function
     * @return RouteInterface
     *
     * @example
     * $group->putFunc('/users/{id}', fn($id) => "Updating user $id");
     */
    public function putFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['PUT']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    /**
     * Adds DELETE route with closure handler
     *
     * @param string $route URI pattern
     * @param callable $func Handler function
     * @return RouteInterface
     *
     * @example
     * $group->deleteFunc('/users/{id}', fn($id) => "Deleting user $id");
     */
    public function deleteFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['DELETE']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    /**
     * Adds PATCH route with closure handler
     *
     * @param string $route URI pattern
     * @param callable $func Handler function
     * @return RouteInterface
     *
     * @example
     * $group->patchFunc('/settings', fn() => "Patching settings");
     */
    public function patchFunc(string $route, callable $func): RouteInterface
    {
        $fullRoute = $this->prefix . $route;
        $routeObject = RouteAnonymousFunc::create($fullRoute, $func, ['PATCH']);
        $routeObject->middleware($this->middleware);
        $this->collection->set($routeObject);
        $this->routes[] = $routeObject;
        return $routeObject;
    }

    /**
     * Adds route for any HTTP method with closure handler
     *
     * @param string $route URI pattern
     * @param callable $func Handler function
     * @return RouteInterface
     *
     * @example
     * $group->anyFunc('/webhook', fn() => "Handling webhook");
     */
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
     * Adds route for specific HTTP methods with closure handler
     *
     * @param array<int, string> $methods Array of HTTP methods
     * @param string $route URI pattern
     * @param callable $func Handler function
     * @return RouteInterface
     *
     * @example
     * $group->matchFunc(['GET', 'POST'], '/form', fn() => "Handling form");
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

    /**
     * Creates nested group
     *
     * @param callable $callback Function to define routes in nested group
     *
     * @example
     * $group->group(function($nested) {
     *     $nested->prefix('/v1');
     *     $nested->get('/users', UserController::class, 'index');
     * });
     */
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
     * Gets all routes in the group
     *
     * @return array<int, RouteInterface> Array of routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
