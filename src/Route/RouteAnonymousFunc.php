<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use Closure;
use FaustVik\Router\interfaces\Routes\RouteAnonymousFuncInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;

final class RouteAnonymousFunc implements RouteAnonymousFuncInterface
{
    private string $route = '';
    private Closure $func;
    private array $methods = [];
    private ?string $alias = null;
    private array $middleware = [];

    public function __construct()
    {
        $this->func = static function () {
        };
    }

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

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getFunc(): Closure
    {
        return $this->func;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function alias(): ?string
    {
        return $this->alias;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function middleware(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }
}
