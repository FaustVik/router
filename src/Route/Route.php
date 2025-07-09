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
    }//end create()

    public function getClass(): string
    {
        return $this->class;
    }//end getClass()

    public function getRoute(): string
    {
        return $this->route;
    }//end getRoute()

    public function getAction(): string
    {
        return $this->action;
    }//end getAction()

    public function getMethods(): array
    {
        return $this->methods;
    }//end getMethods()

    public function alias(): ?string
    {
        return $this->alias;
    }//end alias()

    public function getArg(): array
    {
        return $this->arg;
    }//end getArg()

    public function getMiddleware(): array
    {
        return $this->middleware;
    }//end getMiddleware()

    public function middleware(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }//end middleware()

    public function getValidationRules(): array
    {
        return $this->validationRules;
    }//end getValidationRules()

    public function validate(array $rules): self
    {
        $this->validationRules = $rules;
        return $this;
    }//end validate()
}//end class
