<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use Closure;
use FaustVik\Router\interfaces\Routes\RouteAnonymousFuncInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;
use FaustVik\Router\Validation\ParameterValidationRule;

final class RouteAnonymousFunc implements RouteAnonymousFuncInterface
{
    private string $route = '';
    private Closure $func;
    private array $methods = [];
    private ?string $alias   = null;
    private array $middleware = [];
    private array $validationRules = [];

    public function __construct()
    {
        $this->func = static function () {
        };
    }//end __construct()

    public static function create(string $route, callable $func, array $methods = [], ?string $alias = null): RouteInterface
    {
        $self          = new self();
        $self->route   = $route;
        $self->func    = $func(...);
        $self->methods = $methods;
        $self->alias   = $alias;

        return $self;
    }//end create()

    public function getRoute(): string
    {
        return $this->route;
    }//end getRoute()

    public function getFunc(): Closure
    {
        return $this->func;
    }//end getFunc()

    public function getMethods(): array
    {
        return $this->methods;
    }//end getMethods()

    public function alias(): ?string
    {
        return $this->alias;
    }//end alias()

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
