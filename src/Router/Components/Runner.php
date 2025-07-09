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
use ReflectionNamedType;

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
                $paramName = $reflection_parameter->getName();
                $paramType = $reflection_parameter->getType();
                
                // Если параметр типа Request, создаем Request объект
                if ($paramType && $paramType instanceof ReflectionNamedType && $paramType->getName() === 'FaustVik\Router\Http\Request') {
                    $request = \FaustVik\Router\Http\Request::createFromGlobals();
                    $request = $request->withParams($params);
                    $args[] = $request;
                } elseif (isset($params[$paramName])) {
                    $args[] = $params[$paramName];
                } elseif (!$reflection_parameter->isOptional()) {
                    // Если параметр не опциональный и не найден, добавляем null
                    $args[] = null;
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
                $paramName = $reflection_parameter->getName();
                $paramType = $reflection_parameter->getType();
                
                // Если параметр типа Request, создаем Request объект
                if ($paramType && $paramType instanceof ReflectionNamedType && $paramType->getName() === 'FaustVik\Router\Http\Request') {
                    $request = \FaustVik\Router\Http\Request::createFromGlobals();
                    $request = $request->withParams($params);
                    $atr[] = $request;
                } elseif (isset($params[$paramName])) {
                    $atr[] = $params[$paramName];
                } elseif (!$reflection_parameter->isOptional()) {
                    // Если параметр не опциональный и не найден, добавляем null
                    $atr[] = null;
                }
            }
        }

        if (!method_exists($controller, $route->getAction())) {
            throw new NotFoundMethod($route->getAction(), $route->getClass());
        }

        call_user_func_array([$controller, $route->getAction()], $atr);
    }
}
