<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use FaustVik\Router\interfaces\Routes\RouteClassInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;

final class Route implements RouteClassInterface
{
    protected string  $route;
    protected string  $class;
    protected string  $action;
    protected array   $methods = [];
    protected ?string $alias   = null;
    protected array   $arg     = [];

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
}
