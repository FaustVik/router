<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use FaustVik\Router\Interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\Interfaces\Routes\RouteInterface;

/**
 * Collection of routes
 *
 * Manages all routes in the application. Provides helper methods
 * for common HTTP methods and supports route grouping.
 *
 * @package FaustVik\Router\Route
 */
final class RoutesCollection implements RoutesCollectionInterface
{
    /** @var array<int, RouteInterface> */
    private array $collections = [];

    /**
     * Adds one or more routes to the collection
     *
     * @param RouteInterface ...$routes Routes to add
     *
     * @example
     * $collection->set($route1, $route2, $route3);
     */
    public function set(RouteInterface ...$routes): void
    {
        foreach ($routes as $route) {
            $this->collections[] = $route;
        }
    }

    /**
     * Gets all routes from the collection
     *
     * @return RouteInterface[] Array of all routes
     *
     * @example
     * foreach ($collection->get() as $route) {
     *     echo $route->getRoute();
     * }
     */
    public function get(): array
    {
        return $this->collections;
    }

    /**
     * Adds GET route
     *
     * @param string $route URI pattern
     * @param string $class Controller class
     * @param string $action Controller method
     * @param array<int, mixed> $arg Additional arguments
     * @return RouteInterface
     *
     * @example
     * $collection->addGet('/users', UserController::class, 'index');
     */
    public function addGet(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['GET']);
        $this->set($routeObject);
        return $routeObject;
    }

    /**
     * Adds POST route
     *
     * @param string $route URI pattern
     * @param string $class Controller class
     * @param string $action Controller method
     * @param array<int, mixed> $arg Additional arguments
     * @return RouteInterface
     *
     * @example
     * $collection->addPost('/users', UserController::class, 'store');
     */
    public function addPost(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['POST']);
        $this->set($routeObject);
        return $routeObject;
    }

    /**
     * Adds PUT route
     *
     * @param string $route URI pattern
     * @param string $class Controller class
     * @param string $action Controller method
     * @param array<int, mixed> $arg Additional arguments
     * @return RouteInterface
     *
     * @example
     * $collection->addPut('/users/{id}', UserController::class, 'update');
     */
    public function addPut(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['PUT']);
        $this->set($routeObject);
        return $routeObject;
    }

    /**
     * Adds DELETE route
     *
     * @param string $route URI pattern
     * @param string $class Controller class
     * @param string $action Controller method
     * @param array<int, mixed> $arg Additional arguments
     * @return RouteInterface
     *
     * @example
     * $collection->addDelete('/users/{id}', UserController::class, 'destroy');
     */
    public function addDelete(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['DELETE']);
        $this->set($routeObject);
        return $routeObject;
    }

    /**
     * Adds PATCH route
     *
     * @param string $route URI pattern
     * @param string $class Controller class
     * @param string $action Controller method
     * @param array<int, mixed> $arg Additional arguments
     * @return RouteInterface
     *
     * @example
     * $collection->addPatch('/users/{id}', UserController::class, 'patch');
     */
    public function addPatch(string $route, string $class, string $action, array $arg = []): RouteInterface
    {
        $routeObject = Route::create($route, $class, $action, $arg, ['PATCH']);
        $this->set($routeObject);
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
     * $collection->addAny('/webhook', WebhookController::class, 'handle');
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
     * $collection->addMatch(['GET', 'POST'], '/form', FormController::class, 'handle');
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

    /**
     * Adds GET route with closure handler
     *
     * @param string $route URI pattern
     * @param callable $func Handler function
     * @return RouteInterface
     *
     * @example
     * $collection->addGetFunc('/hello', fn() => "Hello World!");
     */
    public function addGetFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['GET']);
        $this->set($routeObject);
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
     * $collection->addPostFunc('/submit', fn() => "Processing...");
     */
    public function addPostFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['POST']);
        $this->set($routeObject);
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
     * $collection->addPutFunc('/users/{id}', fn($id) => "Updating user $id");
     */
    public function addPutFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['PUT']);
        $this->set($routeObject);
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
     * $collection->addDeleteFunc('/users/{id}', fn($id) => "Deleting user $id");
     */
    public function addDeleteFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['DELETE']);
        $this->set($routeObject);
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
     * $collection->addPatchFunc('/settings', fn() => "Patching settings");
     */
    public function addPatchFunc(string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, ['PATCH']);
        $this->set($routeObject);
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
     * $collection->addAnyFunc('/webhook', fn() => "Handling webhook");
     */
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
     * Adds route for specific HTTP methods with closure handler
     *
     * @param array<int, string> $methods Array of HTTP methods
     * @param string $route URI pattern
     * @param callable $func Handler function
     * @return RouteInterface
     *
     * @example
     * $collection->addMatchFunc(['GET', 'POST'], '/form', fn() => "Handling form");
     */
    public function addMatchFunc(array $methods, string $route, callable $func): RouteInterface
    {
        $routeObject = RouteAnonymousFunc::create($route, $func, $methods);
        $this->set($routeObject);
        return $routeObject;
    }

    /**
     * Creates a route group with prefix
     *
     * @param string $prefix URL prefix for all routes in the group
     * @return RouteGroup
     *
     * @example
     * $collection->prefix('/api')->group(function($group) {
     *     $group->get('/users', UserController::class, 'index');
     * });
     */
    public function prefix(string $prefix): RouteGroup
    {
        $group = new RouteGroup($this);
        $group->prefix($prefix);
        return $group;
    }

    /**
     * Creates a route group with middleware
     *
     * @param array<int, string|callable> $middleware Array of middleware
     * @return RouteGroup
     *
     * @example
     * $collection->middleware([AuthMiddleware::class])->group(function($group) {
     *     $group->get('/profile', ProfileController::class, 'show');
     * });
     */
    public function middleware(array $middleware): RouteGroup
    {
        $group = new RouteGroup($this);
        $group->middleware($middleware);
        return $group;
    }

    /**
     * Creates a route group
     *
     * @param callable $callback Function to define routes in the group
     *
     * @example
     * $collection->group(function($group) {
     *     $group->prefix('/api');
     *     $group->middleware([AuthMiddleware::class]);
     *     $group->get('/users', UserController::class, 'index');
     * });
     */
    public function group(callable $callback): void
    {
        $group = new RouteGroup($this);
        $callback($group);
    }
}
