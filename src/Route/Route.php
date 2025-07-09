<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use FaustVik\Router\interfaces\Routes\RouteClassInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;
use FaustVik\Router\Validation\ParameterValidationRule;

final class Route implements RouteClassInterface
{
    private string $route = '';
    private string $class = '';
    private string $action = '';
    private array $methods = [];
    private ?string $alias   = null;
    private array $arg     = [];
    private array $middleware = [];
    private array $validationRules = [];

    public static function create(string $route, string $class, string $action, array $arg = [], array $methods = [], ?string $alias = null): RouteInterface
    {
        $self          = new self();
        $self->route   = $route;
        $self->class   = $class;
        $self->action  = $action;
        $self->methods = $methods;
        $self->alias   = $alias;
        $self->arg     = $arg;

        return $self;
    }
    public function getClass(): string
    {
        return $this->class;
    }
    public function getRoute(): string
    {
        return $this->route;
    }
    public function getAction(): string
    {
        return $this->action;
    }
    public function getMethods(): array
    {
        return $this->methods;
    }
    public function alias(): ?string
    {
        return $this->alias;
    }
    public function getArg(): array
    {
        return $this->arg;
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
    public function getValidationRules(): array
    {
        return $this->validationRules;
    }
    public function validate(array $rules): self
    {
        $this->validationRules = $rules;
        return $this;
    }
}
