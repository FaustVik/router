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
use FaustVik\Router\interfaces\DI\RouterContainerInterface;
use FaustVik\Router\Http\Request;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;

/**
 * Route handler executor
 *
 * Executes route handlers (controllers or closures) with dependency injection.
 * Resolves constructor dependencies and method parameters from DI container.
 *
 * @package FaustVik\Router\Router\Components
 */
final class Runner implements RunnerInterface
{
    private ?RouterContainerInterface $container = null;

    public function __construct(?RouterContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function setContainer(?RouterContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function getContainer(): ?RouterContainerInterface
    {
        return $this->container;
    }

    /**
     * @param array<string, mixed> $params
     * @throws InvalidTypeRoute
     */
    public function run(RouteInterface $route, array $params = [], ?Request $request = null): void
    {
        if ($route instanceof RouteAnonymousFuncInterface) {
            $this->runAnonymousFunc($route, $params, $request);
            return;
        }

        if ($route instanceof RouteClassInterface) {
            $this->runClass($route, $params, $request);
            return;
        }

        throw new InvalidTypeRoute();
    }

    /**
     * @param array<string, mixed> $params
     * @throws ReflectionException
     */
    public function runAnonymousFunc(
        RouteAnonymousFuncInterface $route,
        array $params = [],
        ?Request $request = null
    ): void {
        $reflection = new ReflectionFunction($route->getFunc());

        $args = [];
        if (!empty($reflection->getParameters())) {
            foreach ($reflection->getParameters() as $reflection_parameter) {
                $paramName = $reflection_parameter->getName();
                $paramType = $reflection_parameter->getType();

                // Если параметр типа Request, используем переданный Request или создаем новый
                if (
                    $paramType && $paramType instanceof ReflectionNamedType &&
                    $paramType->getName() === 'FaustVik\Router\Http\Request'
                ) {
                    if ($request) {
                        $args[] = $request;
                    } else {
                        $req = \FaustVik\Router\Http\Request::createFromGlobals();
                        $req = $req->withParams($params);
                        $args[] = $req;
                    }
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
     * @param array<string, mixed> $params
     * @throws NotFoundClass
     * @throws NotFoundMethod
     */
    public function runClass(RouteClassInterface $route, array $params = [], ?Request $request = null): void
    {
        if (!class_exists($route->getClass())) {
            throw new NotFoundClass($route->getClass());
        }

        $reflection_class = new ReflectionClass($route->getClass());

        // Use DI container if available
        if ($this->container && $this->container->canResolve($route->getClass())) {
            // Преобразуем индексированный массив в ассоциативный для совместимости с контейнером
            $args = $route->getArg();
            /** @var array<string, mixed> $namedArgs */
            $namedArgs = [];
            foreach ($args as $key => $value) {
                // Используем только строковые ключи для PHPStan
                $namedArgs['arg_' . $key] = $value;
            }
            $controller = $this->container->resolve($route->getClass(), $namedArgs);
        } else {
            $controller = $reflection_class->newInstanceArgs($route->getArg());
        }

        $method = $reflection_class->getMethod($route->getAction());

        $atr = [];
        if (!empty($method->getParameters())) {
            foreach ($method->getParameters() as $reflection_parameter) {
                $paramName = $reflection_parameter->getName();
                $paramType = $reflection_parameter->getType();

                // Если параметр типа Request, используем переданный Request или создаем новый
                if (
                    $paramType && $paramType instanceof ReflectionNamedType &&
                    $paramType->getName() === 'FaustVik\Router\Http\Request'
                ) {
                    if ($request) {
                        $atr[] = $request;
                    } else {
                        $req = \FaustVik\Router\Http\Request::createFromGlobals();
                        $req = $req->withParams($params);
                        $atr[] = $req;
                    }
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

        $callable = [$controller, $route->getAction()];
        if (!is_callable($callable)) {
            throw new NotFoundMethod($route->getAction(), $route->getClass());
        }
        
        call_user_func_array($callable, $atr);
    }
}
