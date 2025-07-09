<?php

namespace FaustVik\Router\interfaces\Routes;

use Closure;

interface RouteAnonymousFuncInterface extends RouteInterface
{
    /**
     * @param string      $route
     * @param callable    $func
     * @param array       $methods
     * @param string|null $alias
     *
     * @return RouteInterface
     */
    public static function create(
        string $route,
        callable $func,
        array $methods = [],
        ?string $alias = null
    ): RouteInterface;

    /**
     * @return Closure
     */
    public function getFunc(): Closure;
}
