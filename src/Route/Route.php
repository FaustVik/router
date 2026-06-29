<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use FaustVik\Router\Interfaces\Routes\RouteClassInterface;
use FaustVik\Router\Interfaces\Routes\RouteInterface;

/**
 * Route with controller class handler
 *
 * Represents a route that uses a controller class and method as handler.
 * Supports middleware, constraints, named routes, and method arguments.
 *
 * @package FaustVik\Router\Route
 */
final class Route implements RouteClassInterface
{
    private string $route = '';
    private string $class = '';
    private string $action = '';

    /** @var array<int, string> HTTP методы (GET, POST, и т.д.) */
    private array $methods = [];

    private ?string $alias = null;

    /** @var array<int, mixed> Аргументы для передачи в action */
    private array $arg = [];

    /** @var array<int, string|object|callable> Middleware для маршрута */
    private array $middleware = [];

    private ?string $name = null;

    /** @var array<string, string> Constraints для параметров маршрута */
    private array $constraints = [];

    /**
     * Creates new route
     *
     * @param string $route URI pattern (e.g. '/users/{id}')
     * @param string $class Controller class
     * @param string $action Controller method
     * @param array<int, mixed> $arg Arguments to pass to action
     * @param array<int, string> $methods HTTP methods (GET, POST, etc.)
     * @param string|null $alias Alternative path to route
     * @return RouteInterface
     *
     * @example
     * // With named arguments (recommended)
     * Route::create(
     *     route: '/users/{id}',
     *     class: UserController::class,
     *     action: 'show',
     *     methods: ['GET'],
     *     alias: '/user/{id}'
     * );
     *
     * @example
     * // Simple variant
     * Route::create('/users', UserController::class, 'index');
     *
     * @example
     * // With middleware and constraints
     * Route::create('/admin/users/{id}', AdminController::class, 'edit', methods: ['GET', 'POST'])
     *     ->middleware([AuthMiddleware::class, AdminMiddleware::class])
     *     ->where('id', '\d+')
     *     ->name('admin.users.edit');
     */
    public static function create(
        string $route,
        string $class,
        string $action,
        array $arg = [],
        array $methods = [],
        ?string $alias = null
    ): RouteInterface {
        $self = new self();
        $self->route = $route;
        $self->class = $class;
        $self->action = $action;
        $self->methods = $methods;
        $self->alias = $alias;
        $self->arg = $arg;

        return $self;
    }

    /**
     * Gets controller class
     *
     * @return string Full controller class name
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Gets route URI pattern
     *
     * @return string URI pattern (e.g. '/users/{id}')
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * Gets controller method name
     *
     * @return string Method name (action)
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Gets list of allowed HTTP methods
     *
     * @return array<int, string> Array of HTTP methods (GET, POST, etc.)
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Gets route alias (alternative path)
     *
     * @return string|null Alias or null if not set
     */
    public function alias(): ?string
    {
        return $this->alias;
    }

    /**
     * Sets alias for route
     *
     * @param string $alias Alternative path to route
     * @return self Returns self for fluent interface
     */
    public function setAlias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Gets arguments to pass to action
     *
     * @return array<int, mixed> Array of arguments
     */
    public function getArg(): array
    {
        return $this->arg;
    }

    /**
     * Gets route middleware
     *
     * @return array<int, string|object|callable> Array of middleware
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Sets middleware for route
     *
     * @param array<int, string|object|callable> $middleware Array of middleware (class, object or callable)
     * @return self Returns self for fluent interface
     *
     * @example
     * $route->middleware([AuthMiddleware::class, new LoggingMiddleware()]);
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * Gets route name
     *
     * @return string|null Route name or null if not set
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets route name
     *
     * @param string $name Route name for URL generation
     * @return self Returns self for fluent interface
     *
     * @example
     * $route->name('users.show');
     * // Usage: $router->url('users.show', ['id' => 123]);
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Gets constraints for route parameters
     *
     * @return array<string, string> Associative array [parameter => pattern]
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * Sets constraint for route parameter
     *
     * @param string $param Parameter name from URI (without braces)
     * @param string $pattern Regular expression for validation
     * @return self Returns self for fluent interface
     *
     * @example
     * $route->where('id', '\d+');        // Only digits
     * $route->where('slug', '[a-z-]+');  // Letters and dashes
     */
    public function where(string $param, string $pattern): self
    {
        $this->constraints[$param] = $pattern;
        return $this;
    }
}
