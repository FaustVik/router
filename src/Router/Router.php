<?php

declare(strict_types=1);

namespace FaustVik\Router\Router;

use FaustVik\Router\DI\ContainerAdapterFactory;
use FaustVik\Router\DI\DefaultContainer;
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

use function str_contains;

/**
 * Основной класс роутера
 *
 * Обеспечивает:
 * - Маршрутизацию HTTP запросов
 * - Обработку параметров URL и query string
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

    /** 
     * @var array<string, mixed>|null Query параметры из URI
     * @phpstan-ignore-next-line property.onlyWritten
     */
    private ?array $params = null;

    private ConfigInterface $config;
    private ?RoutesCollectionInterface $collections = null;
    private ?RouterContainerInterface $container = null;

    /** @var array<string, RouteInterface> Индексированные именованные маршруты */
    private array $namedRoutes = [];

    /** @var array<int, string|callable> Глобальные middleware для всех маршрутов */
    private array $globalMiddleware = [];

    /**
     * Конструктор роутера
     *
     * Инициализирует базовую конфигурацию роутера
     */
    public function __construct()
    {
        $this->config = new Config();
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
     *
     * Автоматически индексирует именованные маршруты для быстрого доступа
     */
    public function setCollection(RoutesCollectionInterface $collections): self
    {
        $this->collections = $collections;

        // Индексируем именованные маршруты для быстрого доступа через url()
        $this->namedRoutes = [];
        foreach ($collections->get() as $route) {
            if ($route->getName()) {
                $this->namedRoutes[$route->getName()] = $route;
            }
        }

        return $this;
    }

    /**
     * Обрабатывает Request и возвращает Response без отправки
     *
     * Этот метод выполняет всю логику маршрутизации, но не отправляет ответ клиенту.
     * Полезно для:
     * - Тестирования маршрутов
     * - Создания собственных HTTP серверов
     * - Интеграции с другими фреймворками
     * - Получения ответа для дальнейшей обработки
     *
     * Полный цикл обработки:
     * 1. Парсинг URI для извлечения пути и параметров
     * 2. Поиск подходящего маршрута
     * 3. Проверка HTTP метода
     * 4. Выполнение middleware stack (глобальные + маршрутные)
     * 5. Запуск обработчика маршрута
     * 6. Возврат Response объекта
     *
     * @param Request $request HTTP запрос для обработки
     * @return Response HTTP ответ
     * @throws \FaustVik\Router\exceptions\NoMatch Если маршрут не найден
     * @throws \FaustVik\Router\exceptions\NotAllowedHttpMethod Если HTTP метод не разрешен
     *
     * @example
     * // Тестирование маршрута
     * $request = new Request('GET', '/users/123');
     * $response = $router->handle($request);
     * assert($response->getStatusCode() === 200);
     *
     * @example
     * // Интеграция с другим фреймворком
     * $response = $router->handle($psrRequest->toRequest());
     * return $response->toPsr7Response();
     */
    public function handle(Request $request): Response
    {
        // Устанавливаем URI из запроса
        $this->setUri($request->getUri());

        // Парсим URI для извлечения пути и параметров
        $this->parse();

        // Находим подходящий маршрут
        $matchResult = $this->match();
        $route = $matchResult->getRoute();

        // Объединяем параметры из URL с параметрами из query string
        // Параметры из URL имеют приоритет над query параметры
        $urlParams = $matchResult->getParameters();
        $queryParams = $request->getQuery();
        $allParams = array_merge($queryParams, $urlParams);

        // Проверяем разрешенные HTTP методы (передаем метод из Request)
        $this->check($route, $request->getMethod());

        // Обновляем Request с параметрами маршрута
        $request = $request->withParams($allParams);

        // Создаем middleware stack с финальным обработчиком
        $finalHandler = function (Request $req) use ($route, $allParams): Response {
            ob_start();
            $this->getConfig()->getRunner()->run($route, $allParams, $req);
            $content = ob_get_clean();

            return new Response($content ?: '');
        };

        // Передаем DI контейнер в middleware stack для разрешения зависимостей
        $middlewareStack = new MiddlewareStack($finalHandler, $this->container);

        // Сначала добавляем глобальные middleware (применяются ко всем маршрутам)
        $middlewareStack->addFromArray($this->globalMiddleware);

        // Затем добавляем middleware конкретного маршрута
        $middlewareStack->addFromArray($route->getMiddleware());

        // Выполняем middleware stack и возвращаем результат
        return $middlewareStack->execute($request);
    }

    /**
     * Основной метод запуска роутера
     *
     * Это удобный метод для быстрого старта. Создает Request из глобальных переменных,
     * обрабатывает его через handle() и отправляет ответ клиенту.
     *
     * Для тестирования и более гибкого контроля используйте handle() напрямую.
     *
     * @throws \FaustVik\Router\exceptions\NoMatch Если маршрут не найден
     * @throws \FaustVik\Router\exceptions\NotAllowedHttpMethod Если HTTP метод не разрешен
     *
     * @example
     * // Обычное использование
     * $router = new Router();
     * $router->setCollection($routes);
     * $router->run();
     *
     * @see handle() Для обработки без автоматической отправки ответа
     */
    public function run(): void
    {
        // Создаем Request из глобальных переменных PHP
        $request = Request::createFromGlobals();

        // Обрабатываем запрос через handle()
        $response = $this->handle($request);

        // Отправляем ответ клиенту
        $response->send();
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
     * Использует parse_url() и parse_str() для правильной обработки сложных URL.
     *
     * Поддерживает:
     * - Массивы в query string: ?ids[]=1&ids[]=2
     * - Вложенные параметры: ?user[name]=John&user[age]=30
     * - Специальные символы в параметрах
     *
     * Например: "/users/123?name=John&age=30" ->
     * - $this->uri = "/users/123"
     * - $this->params = ["name" => "John", "age" => "30"]
     */
    protected function parse(): void
    {
        $uri = $this->getUri();

        // Используем parse_url() для корректного разбора URI
        // Это быстрее и надежнее ручного парсинга
        $parsed = parse_url($uri);

        // Извлекаем путь и декодируем его
        $this->uri = isset($parsed['path']) ? urldecode($parsed['path']) : '/';

        // Сохраняем строку параметров для обратной совместимости
        $this->paramsString = $parsed['query'] ?? null;

        // Парсим query string с помощью parse_str()
        // Это правильно обрабатывает массивы и вложенные параметры
        if ($this->paramsString) {
            $parsedParams = [];
            parse_str($this->paramsString, $parsedParams);
            /** @var array<string, mixed> $parsedParams */
            $this->params = $parsedParams;
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
        // После вызова parse() $this->uri всегда инициализирован
        assert($this->uri !== null);
        
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
    /**
     * Проверяет, разрешён ли HTTP метод для маршрута
     *
     * @param RouteInterface $route Маршрут для проверки
     * @param string|null $httpMethod HTTP метод (если null, берется из $_SERVER)
     * @throws \FaustVik\Router\exceptions\NotAllowedHttpMethod
     */
    protected function check(RouteInterface $route, ?string $httpMethod = null): void
    {
        $this->getConfig()->getCheckerHttpMethod()->isAllow($route->getMethods(), $httpMethod);
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
     * 
     * @param array<string, mixed> $config
     */
    public function configureContainer(array $config): self
    {
        if (!$this->container) {
            $this->container = new DefaultContainer();
            $this->config->setContainer($this->container);
        }

        // Обрабатываем обычные привязки
        if (isset($config['bindings']) && is_array($config['bindings'])) {
            foreach ($config['bindings'] as $abstract => $concrete) {
                if (!is_string($abstract)) {
                    throw new \InvalidArgumentException('Binding key must be a string');
                }
                $this->container->bind($abstract, $concrete);
            }
        }

        // Обрабатываем singleton привязки
        if (isset($config['singletons']) && is_array($config['singletons'])) {
            foreach ($config['singletons'] as $abstract => $concrete) {
                if (!is_string($abstract)) {
                    throw new \InvalidArgumentException('Singleton key must be a string');
                }
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

    // ============================================================================
    // Named Routes & URL Generation - Именованные маршруты и генерация URL
    // ============================================================================

    /**
     * Генерирует URL по имени маршрута
     *
     * Заменяет параметры в URL и удаляет опциональные параметры,
     * которые не были предоставлены. Поддерживает query параметры и якоря.
     *
     * @param string $name Имя маршрута
     * @param array<string, string|int> $params Параметры пути для подстановки в {placeholders}
     * @param array<string, string|int|bool|array<mixed>> $query Query параметры для добавления в URL (?key=value)
     * @param string|null $fragment Якорь/фрагмент для добавления в URL (#fragment)
     * @return string Сгенерированный URL
     * @throws \InvalidArgumentException Если маршрут не найден или не все обязательные параметры переданы
     *
     * @example
     * // Базовый пример
     * $router->url('users.show', ['id' => 123]);
     * // => /users/123
     *
     * @example
     * // С named arguments (рекомендуется для сложных URL)
     * $router->url(
     *     name: 'posts.show',
     *     params: ['id' => 456],
     *     query: ['ref' => 'home', 'utm_source' => 'newsletter'],
     *     fragment: 'comments'
     * );
     * // => /posts/456?ref=home&utm_source=newsletter#comments
     *
     * @example
     * // С query параметрами
     * $router->url('users.index', [], ['page' => 2, 'sort' => 'name']);
     * // => /users?page=2&sort=name
     *
     * @example
     * // С якорем
     * $router->url('posts.show', ['id' => 456], [], 'comments');
     * // => /posts/456#comments
     *
     * @example
     * // Опциональные параметры
     * $router->url('posts.index'); // => /posts
     * $router->url('posts.index', ['id' => 456]); // => /posts/456
     */
    public function url(string $name, array $params = [], array $query = [], ?string $fragment = null): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \InvalidArgumentException("Route '{$name}' not found");
        }

        $route = $this->namedRoutes[$name];
        $uri = $route->getRoute();
        $constraints = $route->getConstraints();

        // Валидируем параметры по constraints перед генерацией URL
        foreach ($params as $key => $value) {
            if (isset($constraints[$key])) {
                $pattern = '#^' . $constraints[$key] . '$#';
                if (!preg_match($pattern, (string) $value)) {
                    throw new \InvalidArgumentException(
                        "Parameter '{$key}' with value '{$value}' does not match constraint pattern " .
                        "'{$constraints[$key]}' for route '{$name}'"
                    );
                }
            }
        }

        // Заменяем параметры в URI
        // Поддерживаем форматы: {param}, {param?}, {param:pattern}, {param:pattern?}
        foreach ($params as $key => $value) {
            // Экранируем значение для безопасности
            $escapedValue = rawurlencode((string) $value);

            // Заменяем все варианты параметра
            $replaced = preg_replace(
                [
                    '/\{' . preg_quote($key, '/') . '\?\}/',
                // {param?}
                    '/\{' . preg_quote($key, '/') . ':[^}]+\?\}/',
                // {param:pattern?}
                    '/\{' . preg_quote($key, '/') . '\}/',
                // {param}
                    '/\{' . preg_quote($key, '/') . ':[^}]+\}/',
                // {param:pattern}
                ],
                $escapedValue,
                $uri
            );
            
            // preg_replace может вернуть null при ошибке
            if ($replaced === null) {
                throw new \RuntimeException("Failed to replace parameter '{$key}' in URI");
            }
            $uri = $replaced;
        }

        // Удаляем оставшиеся опциональные параметры
        $uri = preg_replace('/\{[^}]+\?\}/', '', $uri);
        if ($uri === null) {
            throw new \RuntimeException("Failed to process optional parameters in URI");
        }
        
        $uri = preg_replace('/\{[^}]+:[^}]+\?\}/', '', $uri);
        if ($uri === null) {
            throw new \RuntimeException("Failed to process optional parameters in URI");
        }

        // Проверяем, что все обязательные параметры были заполнены
        if (preg_match('/\{([^}?:]+)(?::[^}]+)?\}/', $uri, $matches)) {
            throw new \InvalidArgumentException(
                "Missing required parameter '{$matches[1]}' for route '{$name}'"
            );
        }

        // Очищаем двойные слеши и trailing slash
        $uri = preg_replace('#/{2,}#', '/', $uri);
        if ($uri === null) {
            throw new \RuntimeException("Failed to clean up URI slashes");
        }
        
        $uri = rtrim($uri, '/');

        // Возвращаем корневой путь если URI пустой
        $uri = $uri === '' ? '/' : $uri;

        // Добавляем query параметры если есть
        if (!empty($query)) {
            $uri .= '?' . http_build_query($query);
        }

        // Добавляем якорь/фрагмент если есть
        if ($fragment !== null && $fragment !== '') {
            $uri .= '#' . rawurlencode($fragment);
        }

        return $uri;
    }

    /**
     * Проверяет существование именованного маршрута
     *
     * @param string $name Имя маршрута
     * @return bool True если маршрут существует
     *
     * @example
     * if ($router->has('users.show')) {
     *     $url = $router->url('users.show', ['id' => 123]);
     * }
     */
    public function has(string $name): bool
    {
        return isset($this->namedRoutes[$name]);
    }

    /**
     * Получает все именованные маршруты
     *
     * @return array<string, RouteInterface> Ассоциативный массив [имя => RouteInterface]
     */
    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }

    /**
     * Получает маршрут по имени
     *
     * @param string $name Имя маршрута
     * @return RouteInterface|null Маршрут или null если не найден
     *
     * @example
     * $route = $router->getRouteByName('users.show');
     * if ($route) {
     *     echo $route->getRoute(); // /users/{id}
     * }
     */
    public function getRouteByName(string $name): ?RouteInterface
    {
        return $this->namedRoutes[$name] ?? null;
    }

    // ============================================================================
    // Global Middleware - Глобальные middleware для всех маршрутов
    // ============================================================================

    /**
     * Добавляет глобальный middleware для всех маршрутов
     *
     * Глобальные middleware выполняются перед middleware конкретного маршрута.
     * Это удобно для CORS, логирования, аутентификации и других общих задач.
     *
     * @param string|callable $middleware Middleware класс или callable
     * @return self Возвращает текущий экземпляр для цепочки вызовов
     *
     * @example
     * // Добавление middleware класса
     * $router->addGlobalMiddleware(CorsMiddleware::class);
     *
     * @example
     * // Добавление middleware функции
     * $router->addGlobalMiddleware(fn($request, $next) => $next($request));
     *
     * @example
     * // Цепочка вызовов (рекомендуется)
     * $router->addGlobalMiddleware(CorsMiddleware::class)
     *        ->addGlobalMiddleware(LoggingMiddleware::class)
     *        ->addGlobalMiddleware(RateLimitMiddleware::class);
     */
    public function addGlobalMiddleware(string|callable $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    /**
     * Устанавливает массив глобальных middleware
     *
     * Заменяет все существующие глобальные middleware на новые.
     *
     * @param array<int, string|callable> $middleware Массив middleware
     * @return self Возвращает текущий экземпляр для цепочки вызовов
     *
     * @example
     * $router->setGlobalMiddleware([
     *     CorsMiddleware::class,
     *     LoggingMiddleware::class,
     *     RateLimitMiddleware::class,
     * ]);
     */
    public function setGlobalMiddleware(array $middleware): self
    {
        $this->globalMiddleware = $middleware;
        return $this;
    }

    /**
     * Получает все глобальные middleware
     *
     * @return array<int, string|object|callable> Массив глобальных middleware
     */
    public function getGlobalMiddleware(): array
    {
        return $this->globalMiddleware;
    }

    /**
     * Очищает все глобальные middleware
     *
     * @return self Возвращает текущий экземпляр для цепочки вызовов
     */
    public function clearGlobalMiddleware(): self
    {
        $this->globalMiddleware = [];
        return $this;
    }
}
