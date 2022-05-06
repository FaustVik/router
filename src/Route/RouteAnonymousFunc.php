<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use Closure;
use FaustVik\Router\interfaces\Routes\RouteInterface;
use FaustVik\Router\interfaces\Routes\RouteAnonymousFuncInterface;

final class RouteAnonymousFunc implements RouteAnonymousFuncInterface
{
    protected string  $route;
    protected array   $methods = [];
    protected ?string $alias   = null;
    protected array   $arg     = [];
    protected Closure $func;

    public static function create(string $route, callable $func, array $methods = [], ?string $alias = null): RouteInterface
    {
        $self = new self();

        $self->route   = $route;
        $self->methods = $methods;
        $self->alias   = $alias;
        $self->func    = $func(...);

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @inheritDoc
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @inheritDoc
     */
    public function alias(): ?string
    {
        return $this->alias;
    }

    public function getFunc(): Closure
    {
        return $this->func;
    }
}
