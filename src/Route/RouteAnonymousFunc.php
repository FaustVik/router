<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use Closure;
use FaustVik\Router\Interfaces\Routes\RouteAnonymousFuncInterface;
use FaustVik\Router\Interfaces\Routes\RouteInterface;

/**
 * Route with anonymous function handler
 *
 * Represents a route that uses a closure or callable as its handler.
 * Supports middleware, constraints, and named routes.
 *
 * @package FaustVik\Router\Route
 */
final class RouteAnonymousFunc implements RouteAnonymousFuncInterface
{
    private string $route = '';
    private Closure $func;
    /** @var array<int, string> */
    private array $methods = [];
    private ?string $alias = null;
    /** @var array<int, string|callable> */
    private array $middleware = [];
    private ?string $name = null;
    /** @var array<string, string> */
    private array $constraints = [];

    public function __construct()
    {
        $this->func = static function (): void {
        };
    }

    /**
     * Creates a new route with anonymous function
     *
     * @param string $route URI pattern (e.g. '/users/{id}')
     * @param callable $func Handler function
     * @param array<int, string> $methods HTTP methods
     * @param string|null $alias Alternative route path
     * @return RouteInterface
     *
     * @example
     * // Simple closure route
     * RouteAnonymousFunc::create('/hello', fn() => "Hello World!", ['GET']);
     *
     * @example
     * // With URL parameters
     * RouteAnonymousFunc::create('/users/{id}', fn($id) => "User #$id", ['GET']);
     */
    public static function create(
        string $route,
        callable $func,
        array $methods = [],
        ?string $alias = null
    ): RouteInterface {
        $self = new self();
        $self->route = $route;
        $self->func = $func(...);
        $self->methods = $methods;
        $self->alias = $alias;

        return $self;
    }

    /**
     * Gets the URI pattern
     *
     * @return string URI pattern (e.g. '/users/{id}')
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * Gets the handler function
     *
     * @return Closure Handler closure
     */
    public function getFunc(): Closure
    {
        return $this->func;
    }

    /**
     * Gets allowed HTTP methods
     *
     * @return array<int, string> Array of HTTP methods (GET, POST, etc.)
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Gets route alias
     *
     * @return string|null Alias or null if not set
     */
    public function alias(): ?string
    {
        return $this->alias;
    }

    /**
     * Sets alias for the route
     *
     * @param string $alias Alternative route path
     * @return self For fluent interface
     *
     * @example
     * $route->setAlias('/user/{id}');
     */
    public function setAlias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Gets route middleware
     *
     * @return array<int, string|callable> Array of middleware
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Sets middleware for the route
     *
     * @param array<int, string|callable> $middleware Array of middleware
     * @return self For fluent interface
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
     * Sets route name for URL generation
     *
     * @param string $name Route name
     * @return self For fluent interface
     *
     * @example
     * $route->name('api.users.show');
     * // Usage: $router->url('api.users.show', ['id' => 123]);
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Gets parameter constraints
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
     * @return self For fluent interface
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
