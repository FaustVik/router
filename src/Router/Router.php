<?php

declare(strict_types=1);

namespace FaustVik\Router\Router;

use FaustVik\Router\exceptions\NoMatch;
use FaustVik\Router\exceptions\NotFoundClass;
use FaustVik\Router\exceptions\NotFoundMethod;
use FaustVik\Router\interfaces\Additional\AdditionalActionCollectionInterface;
use FaustVik\Router\interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\interfaces\Router\ConfigInterface;
use FaustVik\Router\interfaces\Router\RoutingInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;
use ReflectionClass;

use function str_contains;

final class Router implements RoutingInterface
{
    protected ?RoutesCollectionInterface $collections = null;
    protected ?AdditionalActionCollectionInterface $action_collection = null;

    protected ?string $uri          = null;
    protected ?string $paramsString = null;
    protected ?string $uriRaw       = null;
    protected array $params = [];
    private ?ConfigInterface $config = null;

    public function setCollection(RoutesCollectionInterface $collection): self
    {
        $this->collections = $collection;
        return $this;
    }

    public function setConfig(ConfigInterface $config): void
    {
        $this->config= $config;
    }

    public function getConfig(): ConfigInterface
    {
        if (!$this->config){
            $this->config = new Config();
        }

        return  $this->config;
    }

    public function run(): void
    {
        $this->parse();
        $route = $this->match($this->uri);
        $this->getConfig()->getRunner()->run($route, $this->params);
    }

    public function setUri(string $uri): self
    {
        $this->uriRaw = $uri;
        return $this;
    }

    public function getUri(): string
    {
        if (!$this->uriRaw) {
            $this->uriRaw = $_SERVER['REQUEST_URI'];
        }

        return $this->uriRaw;
    }

    protected function parse(): void
    {
        $decodeUri = urldecode($this->getUri());
        if (str_contains($decodeUri, '?')) {
            [$this->uri, $this->paramsString] = explode('?', $decodeUri);
        }else{
            $this->uri = $decodeUri;
        }

        if ($this->paramsString){
            $params = explode('&', $this->paramsString);

            $arr = [];

            foreach ($params as $str){
                [$name,$value] = explode('=', $str);
                $arr[$name] = $value;
            }

            $this->params = $arr;
        }
    }

    /**
     * @param string $uri
     *
     * @return RouteInterface
     * @throws NoMatch
     */
    protected function match(string $uri): RouteInterface
    {
        foreach ($this->collections->get() as $route) {
            if (in_array($uri, [$route->alias(), $route->getRoute()], true)) {
                return $route;
            }
        }

        throw new NoMatch($uri);
    }

    protected function initAction(RouteInterface $route): void
    {
        if (!class_exists($route->getClass())) {
            throw new NotFoundClass($route->getClass());
        }

        $reflection_class = new ReflectionClass($route->getClass());

        $controller = $reflection_class->newInstanceArgs($route->getArg());

        $method = $reflection_class->getMethod($route->getAction());

        $atr = [];
        if (!empty($method->getParameters())){
            foreach ($method->getParameters() as $reflection_parameter){
                if (isset($this->params[$reflection_parameter->getName()])){
                   $atr[] = $this->params[$reflection_parameter->getName()];
                }
            }
        }

        if (!method_exists($controller, $route->getAction())) {
            throw new NotFoundMethod($route->getAction(), $route->getClass());
        }

        if ($this->action_collection) {
            $before = $this->action_collection->getBeforeList()[$route->getClass()] ?? null;

            if ($before && method_exists($controller, $before->getAction())) {
                call_user_func_array([$controller, $before->getAction()], []);
            }
        }

        call_user_func_array([$controller, $route->getAction()], $atr);

        if ($this->action_collection) {
            $after = $this->action_collection->getAfterList()[$route->getClass()] ?? null;
            if ($after && method_exists($controller, $after->getAction())) {
                call_user_func_array([$controller, $after->getAction()], []);
            }
        }
    }

    public function setActionCollection(AdditionalActionCollectionInterface $collection): self
    {
        $this->action_collection = $collection;
        return $this;
    }
}
