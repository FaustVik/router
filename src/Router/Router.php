<?php

declare(strict_types=1);

namespace FaustVik\Router\Router;

use FaustVik\Router\exceptions\NoMatch;
use FaustVik\Router\interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\interfaces\Router\Components\ConfigInterface;
use FaustVik\Router\interfaces\Router\RouterInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;
use FaustVik\Router\Router\Components\Config;
use function str_contains;

final class Router implements RouterInterface
{
    private ?RoutesCollectionInterface $collections = null;

    private ?string          $uri          = null;
    private ?string          $paramsString = null;
    private ?string          $uriRaw       = null;
    private array            $params       = [];
    private ?ConfigInterface $config       = null;

    public function setCollection(RoutesCollectionInterface $collection): self
    {
        $this->collections = $collection;
        return $this;
    }

    public function setConfig(ConfigInterface $config): void
    {
        $this->config = $config;
    }

    public function getConfig(): ConfigInterface
    {
        if (!$this->config) {
            $this->config = new Config();
        }

        return $this->config;
    }

    public function run(): void
    {
        $this->parse();
        $route = $this->match();
        $this->check($route);
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
        } else {
            $this->uri = $decodeUri;
        }

        if ($this->paramsString) {
            $params = explode('&', $this->paramsString);

            $arr = [];

            foreach ($params as $str) {
                [$name, $value] = explode('=', $str);
                $arr[$name] = $value;
            }

            $this->params = $arr;
        }
    }

    /**
     *
     * @return RouteInterface
     * @throws NoMatch
     */
    protected function match(): RouteInterface
    {
        return $this->getConfig()->getMatch()->match($this->uri, $this->collections);
    }

    protected function check(RouteInterface $route): void
    {
        $this->getConfig()->getCheckerHttpMethod()::isAllow($route->getMethods());
    }
}
