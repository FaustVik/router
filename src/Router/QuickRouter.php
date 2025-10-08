<?php

declare(strict_types=1);

namespace FaustVik\Router\Router;

use FaustVik\Router\interfaces\Routes\RouteInterface;
use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RouteAnonymousFunc;
use FaustVik\Router\Route\RoutesCollection;

/**
 * QuickRouter - Упрощенный роутер для быстрого старта
 *
 * Обертка над полным Router с простым и понятным API.
 * Идеально подходит для:
 * - Новичков в PHP
 * - Мелких проектов (10-100 маршрутов)
 * - Быстрого прототипирования
 * - Лендингов и простых сайтов
 *
 * @example
 * // Hello World за 5 минут
 * $app = new QuickRouter();
 * $app->get('/', fn() => "Hello World!");
 * $app->get('/users/{id}', fn($id) => "User #$id");
 * $app->run();
 *
 * @package FaustVik\Router
 */
final class QuickRouter
{
    private Router $router;
    private RoutesCollection $routes;

    /**
     * Создает новый QuickRouter
     *
     * @param bool $cache Включить кеширование маршрутов (рекомендуется для production)
     * @param bool $di Включить Dependency Injection контейнер
     *
     * @example
     * // Простой роутер (по умолчанию всё отключено)
     * $app = new QuickRouter();
     *
     * // С кешированием (для production)
     * $app = new QuickRouter(cache: true);
     *
     * // С DI контейнером
     * $app = new QuickRouter(di: true);
     *
     * // С обоими
     * $app = new QuickRouter(cache: true, di: true);
     *
     * // Старый стиль тоже работает (обратная совместимость)
     * $app = new QuickRouter(['cache' => true, 'di' => true]);
     */
    public function __construct(bool|array $cache = false, bool $di = false)
    {
        $this->router = new Router();
        $this->routes = new RoutesCollection();
        $this->router->setCollection($this->routes);

        // Поддержка старого API (массив опций) для обратной совместимости
        if (is_array($cache)) {
            $options = $cache;
            $cache = $options['cache'] ?? false;
            $di = $options['di'] ?? false;
        }

        // По умолчанию всё отключено для простоты
        if ($cache) {
            $this->router->enableCache();
        }

        if ($di) {
            $this->router->enableDI();
        }
    }

    /**
     * Добавляет GET маршрут
     *
     * @param string $uri URI маршрута (например '/users/{id}')
     * @param callable|array $handler Callable функция или [ControllerClass::class, 'method']
     * @return RouteInterface
     *
     * @example
     * // С анонимной функцией
     * $app->get('/', fn() => "Hello World!");
     *
     * // С параметрами из URL
     * $app->get('/users/{id}', fn($id) => "User #$id");
     *
     * // С контроллером
     * $app->get('/users', [UserController::class, 'index']);
     *
     * // С middleware
     * $app->get('/admin', [AdminController::class, 'index'])
     *     ->middleware([AuthMiddleware::class]);
     */
    public function get(string $uri, callable|array $handler): RouteInterface
    {
        return $this->addRoute(
            methods: 'GET',
            uri: $uri,
            handler: $handler
        );
    }

    /**
     * Добавляет POST маршрут
     *
     * @param string $uri URI маршрута
     * @param callable|array $handler Callable функция или [ControllerClass::class, 'method']
     * @return RouteInterface
     *
     * @example
     * $app->post('/users', [UserController::class, 'store']);
     * $app->post('/login', fn() => "Processing login...");
     */
    public function post(string $uri, callable|array $handler): RouteInterface
    {
        return $this->addRoute(
            methods: 'POST',
            uri: $uri,
            handler: $handler
        );
    }

    /**
     * Добавляет PUT маршрут
     *
     * @param string $uri URI маршрута
     * @param callable|array $handler Callable функция или [ControllerClass::class, 'method']
     * @return RouteInterface
     *
     * @example
     * $app->put('/users/{id}', [UserController::class, 'update']);
     */
    public function put(string $uri, callable|array $handler): RouteInterface
    {
        return $this->addRoute(
            methods: 'PUT',
            uri: $uri,
            handler: $handler
        );
    }

    /**
     * Добавляет DELETE маршрут
     *
     * @param string $uri URI маршрута
     * @param callable|array $handler Callable функция или [ControllerClass::class, 'method']
     * @return RouteInterface
     *
     * @example
     * $app->delete('/users/{id}', [UserController::class, 'destroy']);
     */
    public function delete(string $uri, callable|array $handler): RouteInterface
    {
        return $this->addRoute(
            methods: 'DELETE',
            uri: $uri,
            handler: $handler
        );
    }

    /**
     * Добавляет PATCH маршрут
     *
     * @param string $uri URI маршрута
     * @param callable|array $handler Callable функция или [ControllerClass::class, 'method']
     * @return RouteInterface
     *
     * @example
     * $app->patch('/users/{id}', [UserController::class, 'patch']);
     */
    public function patch(string $uri, callable|array $handler): RouteInterface
    {
        return $this->addRoute(
            methods: 'PATCH',
            uri: $uri,
            handler: $handler
        );
    }

    /**
     * Добавляет маршрут для любых HTTP методов
     *
     * @param string $uri URI маршрута
     * @param callable|array $handler Callable функция или [ControllerClass::class, 'method']
     * @return RouteInterface
     *
     * @example
     * $app->any('/webhook', [WebhookController::class, 'handle']);
     */
    public function any(string $uri, callable|array $handler): RouteInterface
    {
        return $this->addRoute(
            methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
            uri: $uri,
            handler: $handler
        );
    }

    /**
     * Добавляет маршрут для указанных HTTP методов
     *
     * @param array $methods Массив HTTP методов ['GET', 'POST']
     * @param string $uri URI маршрута
     * @param callable|array $handler Callable функция или [ControllerClass::class, 'method']
     * @return RouteInterface
     *
     * @example
     * $app->match(['GET', 'POST'], '/form', [FormController::class, 'handle']);
     */
    public function match(array $methods, string $uri, callable|array $handler): RouteInterface
    {
        return $this->addRoute(
            methods: $methods,
            uri: $uri,
            handler: $handler
        );
    }

    /**
     * Создает группу маршрутов с общим префиксом
     *
     * @param string $prefix Префикс для всех маршрутов в группе
     * @param callable $callback Функция для определения маршрутов группы
     * @return self
     *
     * @example
     * $app->prefix('/api', function($app) {
     *     $app->get('/users', [UserController::class, 'index']);
     *     $app->get('/posts', [PostController::class, 'index']);
     * });
     * // Создаст: /api/users и /api/posts
     */
    public function prefix(string $prefix, callable $callback): self
    {
        $group = $this->routes->prefix($prefix);
        $group->group($callback);

        return $this;
    }

    /**
     * Создает группу маршрутов с общим middleware
     *
     * @param array $middleware Массив middleware классов
     * @param callable $callback Функция для определения маршрутов группы
     * @return self
     *
     * @example
     * use FaustVik\Router\Middleware\AuthMiddleware;
     *
     * $app->middleware([AuthMiddleware::class], function($app) {
     *     $app->get('/admin', [AdminController::class, 'index']);
     *     $app->get('/profile', [ProfileController::class, 'show']);
     * });
     */
    public function middleware(array $middleware, callable $callback): self
    {
        $group = $this->routes->middleware($middleware);
        $group->group($callback);

        return $this;
    }

    /**
     * Добавляет глобальный middleware для всех маршрутов
     *
     * Глобальные middleware выполняются перед middleware конкретного маршрута.
     * Это удобно для CORS, логирования, аутентификации и других общих задач.
     *
     * @param string|object|callable $middleware Middleware класс, объект или callable
     * @return self
     *
     * @example
     * use FaustVik\Router\Middleware\CorsMiddleware;
     * use FaustVik\Router\Middleware\LoggingMiddleware;
     *
     * $app = new QuickRouter();
     *
     * // Добавление одного middleware
     * $app->addMiddleware(CorsMiddleware::class);
     *
     * // Добавление нескольких middleware
     * $app->addMiddleware(CorsMiddleware::class)
     *     ->addMiddleware(LoggingMiddleware::class);
     *
     * // Теперь все маршруты будут проходить через эти middleware
     * $app->get('/', fn() => "Hello World!");
     * $app->run();
     */
    public function addMiddleware(string|object|callable $middleware): self
    {
        $this->router->addGlobalMiddleware($middleware);
        return $this;
    }

    /**
     * Устанавливает массив глобальных middleware
     *
     * Заменяет все существующие глобальные middleware на новые.
     *
     * @param array $middleware Массив middleware
     * @return self
     *
     * @example
     * $app->setMiddleware([
     *     CorsMiddleware::class,
     *     LoggingMiddleware::class,
     * ]);
     */
    public function setMiddleware(array $middleware): self
    {
        $this->router->setGlobalMiddleware($middleware);
        return $this;
    }

    /**
     * Получает все глобальные middleware
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->router->getGlobalMiddleware();
    }

    /**
     * Очищает все глобальные middleware
     *
     * @return self
     */
    public function clearMiddleware(): self
    {
        $this->router->clearGlobalMiddleware();
        return $this;
    }

    /**
     * Запускает роутер
     *
     * Обрабатывает текущий HTTP запрос и выполняет соответствующий маршрут.
     *
     * @return void
     *
     * @example
     * $app = new QuickRouter();
     * $app->get('/', fn() => "Hello!");
     * $app->run(); // Обработка запроса
     */
    public function run(): void
    {
        $this->router->run();
    }

    /**
     * Доступ к расширенному API
     *
     * Возвращает полный Router для доступа к продвинутым функциям:
     * - Dependency Injection
     * - Кеширование
     * - Настройка компонентов
     * - И многое другое
     *
     * @return Router Полный Router с расширенным API
     *
     * @example
     * $app = new QuickRouter();
     * $app->get('/', fn() => "Hello!");
     *
     * // Доступ к продвинутым функциям
     * $app->advanced()->enableCache();
     * $app->advanced()->bind(LoggerInterface::class, FileLogger::class);
     *
     * $app->run();
     */
    public function advanced(): Router
    {
        return $this->router;
    }

    /**
     * Внутренний метод для добавления маршрута
     *
     * Использует named arguments для улучшения читаемости.
     *
     * @param string|array $methods HTTP метод(ы)
     * @param string $uri URI маршрута
     * @param callable|array $handler Обработчик
     * @return RouteInterface
     */
    private function addRoute(string|array $methods, string $uri, callable|array $handler): RouteInterface
    {
        $methods = is_string($methods) ? [$methods] : $methods;

        // Если handler - callable функция
        if (is_callable($handler)) {
            $route = RouteAnonymousFunc::create(
                route: $uri,
                func: $handler,
                methods: $methods
            );
            $this->routes->set($route);
            return $route;
        }

        // Если handler - массив [ControllerClass::class, 'method']
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $route = Route::create(
                route: $uri,
                class: $class,
                action: $method,
                arg: [],
                methods: $methods
            );
            $this->routes->set($route);
            return $route;
        }

        throw new \InvalidArgumentException(
            'Handler must be a callable or array [ControllerClass::class, \'method\']'
        );
    }
}

