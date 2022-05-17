<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use Closure;
use FaustVik\Router\interfaces\Routes\RouteAnonymousFuncInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;

final class RouteAnonymousFunc implements RouteAnonymousFuncInterface
{
    private string  $route;
    private array   $methods = [];
    private ?string $alias   = null;
    private Closure $func;

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
