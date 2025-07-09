<?php

namespace FaustVik\Router\interfaces\Router\Components;

use FaustVik\Router\interfaces\Routes\RouteAnonymousFuncInterface;
use FaustVik\Router\interfaces\Routes\RouteClassInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;
use FaustVik\Router\Http\Request;

interface RunnerInterface
{
    /**
     * Common method to run, it will parse what specific type of route it is
     *
     * @param RouteInterface $route
     * @param array          $params
     * @param Request|null   $request
     *
     * @return void
     */
    public function run(RouteInterface $route, array $params = [], ?Request $request = null): void;

    /**
     * Run only RouteAnonymousFuncInterface route
     *
     * @param RouteAnonymousFuncInterface $route
     * @param array                       $params
     * @param Request|null                $request
     *
     * @return void
     */
    public function runAnonymousFunc(RouteAnonymousFuncInterface $route, array $params = [], ?Request $request = null): void;

    /**
     * Run only RouteClassInterface route
     *
     * @param RouteClassInterface $route
     * @param array               $params
     * @param Request|null        $request
     *
     * @return void
     */
    public function runClass(RouteClassInterface $route, array $params = [], ?Request $request = null): void;
}//end interface
