<?php

declare(strict_types=1);

namespace FaustVik\Router\Interfaces\Router\Components;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Interfaces\Routes\RouteAnonymousFuncInterface;
use FaustVik\Router\Interfaces\Routes\RouteClassInterface;
use FaustVik\Router\Interfaces\Routes\RouteInterface;

interface RunnerInterface
{
    /**
     * Common method to run, it will parse what specific type of route it is
     *
     * @param RouteInterface $route
     * @param array<string, mixed> $params
     * @param Request|null   $request
     *
     * @return void
     */
    public function run(RouteInterface $route, array $params = [], ?Request $request = null): void;

    /**
     * Run only RouteAnonymousFuncInterface route
     *
     * @param RouteAnonymousFuncInterface $route
     * @param array<string, mixed> $params
     * @param Request|null                $request
     *
     * @return void
     */
    public function runAnonymousFunc(
        RouteAnonymousFuncInterface $route,
        array $params = [],
        ?Request $request = null
    ): void;

    /**
     * Run only RouteClassInterface route
     *
     * @param RouteClassInterface $route
     * @param array<string, mixed> $params
     * @param Request|null        $request
     *
     * @return void
     */
    public function runClass(RouteClassInterface $route, array $params = [], ?Request $request = null): void;
}
