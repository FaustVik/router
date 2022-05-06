<?php

namespace FaustVik\Router\interfaces;

use FaustVik\Router\interfaces\Routes\RouteAnonymousFuncInterface;
use FaustVik\Router\interfaces\Routes\RouteClassInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;

interface RunnerInterface
{
    public function run(RouteInterface $route, array $params= []): void;

    public function runAnonymousFunc(RouteAnonymousFuncInterface $route): void;

    public function runClass(RouteClassInterface $route, array $params= []): void;
}
