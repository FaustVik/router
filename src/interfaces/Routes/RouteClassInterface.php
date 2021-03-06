<?php

namespace FaustVik\Router\interfaces\Routes;

interface RouteClassInterface extends RouteInterface
{
    /**
     * @param string      $route
     * @param string      $class
     * @param string      $action
     * @param array       $arg
     * @param array       $methods
     * @param string|null $alias
     *
     * @return RouteInterface
     */
    public static function create(
        string  $route,
        string  $class,
        string  $action,
        array   $arg = [],
        array   $methods = [],
        ?string $alias = null
    ): RouteInterface;

    /**
     * Get class name
     *
     * @return string
     */
    public function getClass(): string;

    /**
     * Get action name class
     *
     * @return string
     */
    public function getAction(): string;

    /**
     * Arguments for init Class
     *
     * @return array
     */
    public function getArg(): array;
}
