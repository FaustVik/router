<?php

namespace FaustVik\Router\interfaces\Routes;

use Closure;

interface RouteAnonymousFuncInterface extends RouteInterface
{
    public static function create(
        string $route,
        callable $func,
        array $methods = [],
        ?string $alias = null
    ): RouteInterface;

    public function getFunc(): Closure;
}
