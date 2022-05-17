<?php

namespace FaustVik\Router\interfaces\Router\Components;

use FaustVik\Router\interfaces\Routes\RouteAnonymousFuncInterface;
use FaustVik\Router\interfaces\Routes\RouteClassInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;

interface RunnerInterface
{
    /**
     * Common method to run, it will parse what specific type of route it is
     *
     * @param RouteInterface $route
     * @param array          $params
     *
     * @return void
     */
    public function run(RouteInterface $route, array $params= []): void;

    /**
     * Run only RouteAnonymousFuncInterface route
     *
     * @param RouteAnonymousFuncInterface $route
     *
     * @return void
     */
    public function runAnonymousFunc(RouteAnonymousFuncInterface $route): void;

    /**
     * Run only RouteClassInterface route
     *
     * @param RouteClassInterface $route
     * @param array               $params
     *
     * @return void
     */
    public function runClass(RouteClassInterface $route, array $params= []): void;
}
