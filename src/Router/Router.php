<?php

declare(strict_types=1);

namespace FaustVik\Router\Router;

use FaustVik\Router\DI\ContainerAdapterFactory;
use FaustVik\Router\DI\DefaultContainer;
use FaustVik\Router\exceptions\ValidationException;
use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\interfaces\Cache\CacheableRouterInterface;
use FaustVik\Router\interfaces\Cache\CacheInterface;
use FaustVik\Router\interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\interfaces\DI\RouterContainerInterface;
use FaustVik\Router\interfaces\Router\Components\ConfigInterface;
use FaustVik\Router\interfaces\Router\RouterInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;
use FaustVik\Router\Middleware\MiddlewareStack;
use FaustVik\Router\Router\Components\Config;
use FaustVik\Router\Router\Components\matching\MatchResult;
use FaustVik\Router\Validation\ParameterValidator;

use function str_contains;

/**
 * Основной класс роутера
 *
 * Обеспечивает:
 * - Маршрутизацию HTTP запросов
 * - Обработку параметров URL и query string
 * - Валидацию параметров
 * - Поддержку middleware
 * - Кеширование результатов маршрутизации
 * - Dependency Injection контейнер
 *
 * @package FaustVik\Router\Router
 */
final class Router implements RouterInterface, CacheableRouterInterface
{
    private ?string $uriRaw = null;
    private ?string $uri = null;
    private ?string $paramsString = null;
    private ?array $params = null;
    private ConfigInterface $config;
    private ?RoutesCollectionInterface $collections = null;
    private ?ParameterValidator $parameterValidator = null;
    private ?RouterContainerInterface $container = null;

    /**
     * Конструктор роутера
     *
     * Инициализирует базовую конфигурацию и валидатор параметров
     */
    public function __construct()
    {
        $this->config = new Config();
        $this->parameterValidator = new ParameterValidator();
    }

    /**
     * Устанавливает конфигурацию роутера
     */
    public function setConfig(ConfigInterface $config): void
    {
        $this->config = $config;
    }

    /**
     * Получает текущую конфигурацию роутера
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * Устанавливает коллекцию маршрутов
     */
    public function setCollection(RoutesCollectionInterface $collections): self
    {
        $this->collections = $collections;
        return $this;
    }

    /**
     * Основной метод запуска роутера
     *
     * Выполняет следующие шаги:
     * 1. Парсит URI запроса
     * 2. Находит подходящий маршрут
     * 3. Валидирует параметры
     * 4. Проверяет HTTP метод
     * 5. Создает объект запроса
     * 6. Выполняет middleware stack
     * 7. Запускает контроллер
     * 8. Отправляет ответ
     *
     * @throws \FaustVik\Router\exceptions\ValidationException Если параметры не прошли валидацию
     * @throws \FaustVik\Router\exceptions\NoMatch Если маршрут не найден
     * @throws \FaustVik\Router\exceptions\NotAllowedHttpMethod Если HTTP метод не разрешен
     */
    public function run(): void
    {
        // Парсим URI для извлечения пути и параметров
        $this->parse();

        // Находим подходящий маршрут
        $matchResult = $this->match();
        $route = $matchResult->getRoute();

        // Объединяем параметры из URL с параметрами из query string
        // Параметры из URL имеют приоритет над query параметрами
        $urlParams = $matchResult->getParameters();
        $allParams = array_merge($this->params ?? [], $urlParams);

        // Валидируем параметры маршрута
        $this->validateParameters($route, $urlParams);

        // Проверяем разрешенные HTTP методы
        $this->check($route);

        // Создаем объект запроса с полными данными
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $server = $_SERVER;

        $request = new Request($method, $this->uri, $allParams, $this->params ?? [], $headers, $server);

        // Создаем middleware stack с финальным обработчиком
        $finalHandler = function (Request $request) use ($route, $allParams): Response {
            ob_start();
            $this->getConfig()->getRunner()->run($route, $allParams, $request);
            $content = ob_get_clean();

            return new Response($content ?: '');
        };

        $middlewareStack = new MiddlewareStack($finalHandler);
        $middlewareStack->addFromArray($route->getMiddleware());

        // Выполняем middleware stack
        $response = $middlewareStack->execute($request);

        // Отправляем ответ клиенту
        $response->send();
    }

    /**
     * Валидирует параметры маршрута
     *
     * Проверяет параметры согласно правилам валидации, определенным в маршруте.
     * При ошибке валидации возвращает HTTP 400 и завершает выполнение.
     *
     * @throws ValidationException Если параметры не прошли валидацию
     */
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

    /**
     * Устанавливает URI для обработки
     *
     * Используется для тестирования или когда нужно обработать
     * конкретный URI не из $_SERVER
     */
    public function setUri(string $uri): self
    {
        $this->uriRaw = $uri;
        return $this;
    }

    /**
     * Получает URI для обработки
     *
     * Если URI не был установлен явно, берет из $_SERVER['REQUEST_URI']
     */
    public function getUri(): string
    {
        if (!$this->uriRaw) {
            $this->uriRaw = $_SERVER['REQUEST_URI'] ?? '/';
        }

        return $this->uriRaw;
    }

    /**
     * Парсит URI запроса
     *
     * Разделяет URI на путь и параметры query string.
     * Декодирует URI и извлекает параметры в массив.
     *
     * Например: "/users/123?name=John&age=30" ->
     * - $this->uri = "/users/123"
     * - $this->params = ["name" => "John", "age" => "30"]
     */
    protected function parse(): void
    {
        $decodeUri = urldecode($this->getUri());

        // Разделяем URI на путь и параметры
        if (str_contains($decodeUri, '?')) {
            [$this->uri, $this->paramsString] = explode('?', $decodeUri);
        } else {
            $this->uri = $decodeUri;
        }

        // Парсим параметры query string
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

    /**
     * Находит подходящий маршрут для URI
     *
     * Использует компонент Matching для поиска маршрута,
     * который соответствует текущему URI.
     *
     * @throws \FaustVik\Router\exceptions\NoMatch Если подходящий маршрут не найден
     */
    protected function match(): MatchResult
    {
        return $this->getConfig()->getMatch()->match($this->uri, $this->collections);
    }

    /**
     * Проверяет разрешенные HTTP методы для маршрута
     *
     * Использует компонент CheckerHttpMethod для проверки,
     * разрешен ли текущий HTTP метод для найденного маршрута.
     *
     * @throws \FaustVik\Router\exceptions\NotAllowedHttpMethod Если HTTP метод не разрешен
     */
    protected function check(RouteInterface $route): void
    {
        $this->getConfig()->getCheckerHttpMethod()->isAllow($route->getMethods());
    }

    /**
     * Включает кеширование маршрутов
     *
     * Активирует систему кеширования для ускорения поиска маршрутов.
     * Результаты поиска маршрутов будут сохраняться в кеше.
     */
    public function enableCache(): void
    {
        $this->config->enableCache();
    }

    /**
     * Отключает кеширование маршрутов
     *
     * Деактивирует систему кеширования.
     * Поиск маршрутов будет выполняться каждый раз заново.
     */
    public function disableCache(): void
    {
        $this->config->disableCache();
    }

    /**
     * Проверяет, включено ли кеширование
     */
    public function isCacheEnabled(): bool
    {
        return $this->config->isCacheEnabled();
    }

    /**
     * Устанавливает кеш-драйвер
     */
    public function setCache(CacheInterface $cache): void
    {
        $this->config->setCache($cache);
    }

    /**
     * Получает текущий кеш-драйвер
     */
    public function getCache(): ?CacheInterface
    {
        return $this->config->getCache();
    }

    /**
     * Очищает кеш маршрутов
     *
     * Удаляет все сохраненные результаты поиска маршрутов.
     */
    public function clearRouteCache(): bool
    {
        return $this->config->clearCache();
    }

    /**
     * Генерирует ключ кеша для текущего URI
     */
    public function getCacheKey(): string
    {
        return 'router_cache_' . md5($this->getUri());
    }

    // ============================================================================
    // DI Container methods - Методы для работы с Dependency Injection контейнером
    // ============================================================================

    /**
     * Включает Dependency Injection с опциональной конфигурацией
     *
     * Создает встроенный контейнер DefaultContainer и настраивает его
     * с помощью переданной closure-функции.
     *
     * @param \Closure|null $configurator Функция для настройки контейнера
     * @return self Возвращает текущий экземпляр для цепочки вызовов
     *
     * @example
     * $router->enableDI(function($container) {
     *     $container->singleton(UserService::class, UserService::class);
     *     $container->bind(LoggerInterface::class, FileLogger::class);
     * });
     */
    public function enableDI(\Closure $configurator = null): self
    {
        if ($configurator) {
            $this->container = DefaultContainer::withClosure($configurator);
        } else {
            $this->container = new DefaultContainer();
        }

        // Обновляем конфигурацию с контейнером
        $this->config->setContainer($this->container);

        return $this;
    }

    /**
     * Устанавливает кастомный контейнер
     *
     * Позволяет использовать внешний контейнер (Symfony, PHP-DI, Pimple и др.)
     * через систему адаптеров.
     *
     * @param object $container Экземпляр внешнего контейнера
     * @return self Возвращает текущий экземпляр для цепочки вызовов
     *
     * @example
     * $phpDiContainer = new DI\Container();
     * $router->setContainer($phpDiContainer);
     */
    public function setContainer(object $container): self
    {
        $this->container = ContainerAdapterFactory::createFor($container);

        // Обновляем конфигурацию с контейнером
        $this->config->setContainer($this->container);

        return $this;
    }

    /**
     * Получает текущий DI контейнер
     */
    public function getContainer(): ?RouterContainerInterface
    {
        return $this->container;
    }

    /**
     * Проверяет, включен ли DI
     */
    public function isDIEnabled(): bool
    {
        return $this->container !== null;
    }

    /**
     * Настраивает контейнер с помощью массива конфигурации
     *
     * Позволяет настроить контейнер декларативно через массив.
     * Поддерживает секции 'bindings' и 'singletons'.
     *
     * @param array $config Массив конфигурации контейнера
     * @return self Возвращает текущий экземпляр для цепочки вызовов
     *
     * @example
     * $router->configureContainer([
     *     'bindings' => [
     *         LoggerInterface::class => FileLogger::class,
     *         MailerInterface::class => SmtpMailer::class,
     *     ],
     *     'singletons' => [
     *         DatabaseConnection::class => DatabaseConnection::class,
     *     ]
     * ]);
     */
    public function configureContainer(array $config): self
    {
        if (!$this->container) {
            $this->container = new DefaultContainer();
            $this->config->setContainer($this->container);
        }

        // Обрабатываем обычные привязки
        if (isset($config['bindings'])) {
            foreach ($config['bindings'] as $abstract => $concrete) {
                $this->container->bind($abstract, $concrete);
            }
        }

        // Обрабатываем singleton привязки
        if (isset($config['singletons'])) {
            foreach ($config['singletons'] as $abstract => $concrete) {
                $this->container->singleton($abstract, $concrete);
            }
        }

        return $this;
    }

    /**
     * Привязывает сервис к контейнеру
     *
     * Регистрирует привязку абстракции к конкретной реализации.
     * При каждом запросе будет создаваться новый экземпляр.
     *
     * @param string $abstract Абстракция (интерфейс или класс)
     * @param mixed $concrete Конкретная реализация (класс, closure или экземпляр)
     * @return self Возвращает текущий экземпляр для цепочки вызовов
     *
     * @example
     * $router->bind(LoggerInterface::class, FileLogger::class);
     * $router->bind('config', function() { return new Config(); });
     */
    public function bind(string $abstract, mixed $concrete): self
    {
        if (!$this->container) {
            $this->container = new DefaultContainer();
            $this->config->setContainer($this->container);
        }

        $this->container->bind($abstract, $concrete);
        return $this;
    }

    /**
     * Привязывает singleton к контейнеру
     *
     * Регистрирует привязку абстракции к конкретной реализации как singleton.
     * Экземпляр будет создан только один раз и переиспользован.
     *
     * @param string $abstract Абстракция (интерфейс или класс)
     * @param mixed $concrete Конкретная реализация (класс, closure или экземпляр)
     * @return self Возвращает текущий экземпляр для цепочки вызовов
     *
     * @example
     * $router->singleton(DatabaseConnection::class, DatabaseConnection::class);
     * $router->singleton('cache', function() { return new RedisCache(); });
     */
    public function singleton(string $abstract, mixed $concrete): self
    {
        if (!$this->container) {
            $this->container = new DefaultContainer();
            $this->config->setContainer($this->container);
        }

        $this->container->singleton($abstract, $concrete);
        return $this;
    }
}
