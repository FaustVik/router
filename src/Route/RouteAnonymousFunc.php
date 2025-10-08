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
        $this->func = static function () {
        };
    }

    /**
     * @param array<int, string> $methods
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

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getFunc(): Closure
    {
        return $this->func;
    }

    /**
     * @return array<int, string>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function alias(): ?string
    {
        return $this->alias;
    }

    /**
     * Устанавливает alias для маршрута
     *
     * @param string $alias Альтернативный путь к маршруту
     * @return self Для fluent interface
     */
    public function setAlias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * @return array<int, string|callable>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * @param array<int, string|callable> $middleware
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    public function where(string $param, string $pattern): self
    {
        $this->constraints[$param] = $pattern;
        return $this;
    }
}
