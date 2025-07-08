<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components;

use FaustVik\Router\exceptions\InvalidTypeRoute;
use FaustVik\Router\exceptions\NotFoundClass;
use FaustVik\Router\exceptions\NotFoundMethod;
use FaustVik\Router\interfaces\Router\Components\RunnerInterface;
use FaustVik\Router\interfaces\Routes\RouteAnonymousFuncInterface;
use FaustVik\Router\interfaces\Routes\RouteClassInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;

final class Runner implements RunnerInterface
{
    /**
     * @throws NotFoundMethod
     * @throws ReflectionException
     * @throws NotFoundClass
     * @throws InvalidTypeRoute
     */
    public function run(RouteInterface $route, array $params = []): void
    {
        if ($route instanceof RouteAnonymousFuncInterface) {
            $this->runAnonymousFunc($route, $params);
            return;
        }

        if ($route instanceof RouteClassInterface) {
            $this->runClass($route, $params);
            return;
        }

        throw new InvalidTypeRoute();
    }

    /**
     * @param RouteAnonymousFuncInterface $route
     * @param array $params
     *
     * @return void
     * @throws ReflectionException
     */
    public function runAnonymousFunc(RouteAnonymousFuncInterface $route, array $params = []): void
    {
        $reflection = new ReflectionFunction($route->getFunc());
        
        $args = [];
        if (!empty($reflection->getParameters())) {
            foreach ($reflection->getParameters() as $reflection_parameter) {
                if (isset($params[$reflection_parameter->getName()])) {
                    $args[] = $params[$reflection_parameter->getName()];
                }
            }
        }

        call_user_func_array($route->getFunc(), $args);
    }

    /**
     * @param RouteClassInterface $route
     * @param array               $params
     *
     * @return void
     * @throws NotFoundClass
     * @throws NotFoundMethod
     * @throws ReflectionException
     */
    public function runClass(RouteClassInterface $route, array $params = []): void
    {
        if (!class_exists($route->getClass())) {
            throw new NotFoundClass($route->getClass());
        }

        $reflection_class = new ReflectionClass($route->getClass());

        $controller = $reflection_class->newInstanceArgs($route->getArg());

        $method = $reflection_class->getMethod($route->getAction());

        $atr = [];
        if (!empty($method->getParameters())) {
            foreach ($method->getParameters() as $reflection_parameter) {
                if (isset($params[$reflection_parameter->getName()])) {
                    $atr[] = $params[$reflection_parameter->getName()];
                }
            }
        }

        if (!method_exists($controller, $route->getAction())) {
            throw new NotFoundMethod($route->getAction(), $route->getClass());
        }

        call_user_func_array([$controller, $route->getAction()], $atr);
    }
}
