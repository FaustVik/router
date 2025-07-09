<?php

declare(strict_types=1);

namespace FaustVik\Router\Router;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\interfaces\Router\Components\ConfigInterface;
use FaustVik\Router\interfaces\Router\RouterInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;
use FaustVik\Router\Middleware\MiddlewareStack;
use FaustVik\Router\Router\Components\Config;
use FaustVik\Router\Router\Components\MatchResult;
use FaustVik\Router\Validation\ParameterValidator;
use FaustVik\Router\exceptions\ValidationException;
use FaustVik\Router\interfaces\Cache\CacheableRouterInterface;
use FaustVik\Router\interfaces\Cache\CacheInterface;
use function str_contains;

final class Router implements RouterInterface, CacheableRouterInterface
{
    private ?string $uriRaw = null;
    private ?string $uri = null;
    private ?string $paramsString = null;
    private ?array $params = null;
    private ConfigInterface $config;
    private ?RoutesCollectionInterface $collections = null;
    private ?ParameterValidator $parameterValidator = null;

    public function __construct()
    {
        $this->config = new Config();
        $this->parameterValidator = new ParameterValidator();
    }

    public function setConfig(ConfigInterface $config): void
    {
        $this->config = $config;
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    public function setCollection(RoutesCollectionInterface $collections): self
    {
        $this->collections = $collections;
        return $this;
    }

    public function run(): void
    {
        $this->parse();
        $matchResult = $this->match();
        $route = $matchResult->getRoute();
        
        // Объединяем параметры из URL с параметрами из query string
        // Параметры из URL имеют приоритет над query параметрами
        $urlParams = $matchResult->getParameters();
        $allParams = array_merge($this->params ?? [], $urlParams);
        
        // Валидируем параметры
        $this->validateParameters($route, $urlParams);
        
        $this->check($route);
        
        // Создаем объект запроса
        $request = Request::createFromGlobals()
            ->withUri($this->uri)
            ->withParams($allParams);
        
        // Создаем middleware stack
        $finalHandler = function(Request $request) use ($route, $allParams): Response {
            ob_start();
            $this->getConfig()->getRunner()->run($route, $allParams);
            $content = ob_get_clean();
            
            return new Response($content ?: '');
        };
        
        $middlewareStack = new MiddlewareStack($finalHandler);
        $middlewareStack->addFromArray($route->getMiddleware());
        
        // Выполняем middleware stack
        $response = $middlewareStack->execute($request);
        
        // Отправляем ответ
        $response->send();
    }

    private function validateParameters(RouteInterface $route, array $parameters): void
    {
        $validationRules = $route->getValidationRules();
        if (empty($validationRules)) {
            return;
        }

        try {
            $this->parameterValidator->validate($parameters, $validationRules);
        } catch (ValidationException $e) {
            // Здесь можно настроить обработку ошибок валидации
            // Пока просто выводим ошибку и завершаем выполнение
            http_response_code(400);
            echo "Validation Error: " . $e->getMessage();
            exit;
        }
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
                if (str_contains($str, '=')) {
                    [$name, $value] = explode('=', $str);
                    $arr[$name] = $value;
                }
            }

            $this->params = $arr;
        }
    }

    protected function match(): MatchResult
    {
        return $this->getConfig()->getMatch()->match($this->uri, $this->collections);
    }

    protected function check(RouteInterface $route): void
    {
        $this->getConfig()->getCheckerHttpMethod()::isAllow($route->getMethods());
    }

    public function enableCache(): void
    {
        $this->config->enableCache();
    }

    public function disableCache(): void
    {
        $this->config->disableCache();
    }

    public function isCacheEnabled(): bool
    {
        return $this->config->isCacheEnabled();
    }

    public function setCache(CacheInterface $cache): void
    {
        $this->config->setCache($cache);
    }

    public function getCache(): ?CacheInterface
    {
        return $this->config->getCache();
    }

    public function clearRouteCache(): bool
    {
        return $this->config->clearCache();
    }

    public function getCacheKey(): string
    {
        return 'router_cache_' . md5($this->getUri());
    }
}
