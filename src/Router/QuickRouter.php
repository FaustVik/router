<?php

declare(strict_types=1);

namespace FaustVik\Router\Router;

use FaustVik\Router\interfaces\Middleware\MiddlewareInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;
use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RouteAnonymousFunc;
use FaustVik\Router\Route\RoutesCollection;

/**
 * QuickRouter - Simplified router for quick start
 *
 * Wrapper over full Router with simple and clear API.
 * Perfect for:
 * - PHP beginners
 * - Small projects (10-100 routes)
 * - Rapid prototyping
 * - Landing pages and simple websites
 *
 * @example
 * // Hello World in 5 minutes
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
     * Creates new QuickRouter
     *
     * @param bool|array<string, bool> $cache Enable route caching or options array (backward compatibility)
     * @param bool $di Enable Dependency Injection container
     *
     * @example
     * // Simple router (everything disabled by default)
     * $app = new QuickRouter();
     *
     * @example
     * // With named arguments (recommended)
     * $app = new QuickRouter(cache: true, di: true);
     *
     * @example
     * // Only caching (for production)
     * $app = new QuickRouter(cache: true);
     *
     * @example
     * // Only DI container
     * $app = new QuickRouter(di: true);
     *
     * @example
     * // Old style with array (backward compatibility)
     * $app = new QuickRouter(['cache' => true, 'di' => true]);
     */
    public function __construct(bool|array $cache = false, bool $di = false)
    {
        $this->router = new Router();
        $this->routes = new RoutesCollection();
        $this->router->setCollection($this->routes);

        // Support old API (options array) for backward compatibility
        if (is_array($cache)) {
            $options = $cache;
            $cache = $options['cache'] ?? false;
            $di = $options['di'] ?? false;
        }

        // Everything disabled by default for simplicity
        if ($cache) {
            $this->router->enableCache();
        }

        if ($di) {
            $this->router->enableDI();
        }
    }

    /**
     * Adds GET route
     *
     * @param string $uri Route URI (e.g. '/users/{id}')
     * @param callable|array<int, mixed> $handler Callable function or [ControllerClass::class, 'method']
     * @return RouteInterface
     *
     * @example
     * // With anonymous function
     * $app->get('/', fn() => "Hello World!");
     *
     * // With URL parameters
     * $app->get('/users/{id}', fn($id) => "User #$id");
     *
     * // With controller
     * $app->get('/users', [UserController::class, 'index']);
     *
     * // With middleware
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
     * Adds POST route
     *
     * @param string $uri Route URI
     * @param callable|array<int, mixed> $handler Callable function or [ControllerClass::class, 'method']
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
     * Adds PUT route
     *
     * @param string $uri Route URI
     * @param callable|array<int, mixed> $handler Callable function or [ControllerClass::class, 'method']
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
     * Adds DELETE route
     *
     * @param string $uri Route URI
     * @param callable|array<int, mixed> $handler Callable function or [ControllerClass::class, 'method']
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
     * Adds PATCH route
     *
     * @param string $uri Route URI
     * @param callable|array<int, mixed> $handler Callable function or [ControllerClass::class, 'method']
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
     * Adds route for any HTTP methods
     *
     * @param string $uri Route URI
     * @param callable|array<int, mixed> $handler Callable function or [ControllerClass::class, 'method']
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
     * Adds route for specified HTTP methods
     *
     * @param array<int, string> $methods Array of HTTP methods ['GET', 'POST']
     * @param string $uri Route URI
     * @param callable|array<int, mixed> $handler Callable function or [ControllerClass::class, 'method']
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
     * Creates route group with common prefix
     *
     * @param string $prefix Prefix for all routes in group
     * @param callable $callback Function to define group routes
     * @return self
     *
     * @example
     * $app->prefix('/api', function($app) {
     *     $app->get('/users', [UserController::class, 'index']);
     *     $app->get('/posts', [PostController::class, 'index']);
     * });
     * // Creates: /api/users and /api/posts
     */
    public function prefix(string $prefix, callable $callback): self
    {
        $group = $this->routes->prefix($prefix);
        $group->group($callback);

        return $this;
    }

    /**
     * Creates route group with common middleware
     *
     * @param array $middleware Array of middleware classes
     * @param callable $callback Function to define group routes
     * @param array<int, string|callable> $middleware
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
     * Adds global middleware for all routes
     *
     * Global middleware executes before route-specific middleware.
     * Useful for CORS, logging, authentication and other common tasks.
     *
     * @param string|callable $middleware Middleware class or callable
     * @return self Returns self for fluent interface
     *
     * @example
     * use FaustVik\Router\Middleware\CorsMiddleware;
     * use FaustVik\Router\Middleware\LoggingMiddleware;
     *
     * $app = new QuickRouter();
     *
     * // Adding single middleware
     * $app->addMiddleware(CorsMiddleware::class);
     *
     * @example
     * // Method chaining (recommended)
     * $app->addMiddleware(CorsMiddleware::class)
     *     ->addMiddleware(LoggingMiddleware::class)
     *     ->addMiddleware(RateLimitMiddleware::class);
     *
     * // Now all routes will pass through these middleware
     * $app->get('/', fn() => "Hello World!");
     * $app->run();
     */
    public function addMiddleware(string|callable|MiddlewareInterface $middleware): self
    {
        $this->router->addGlobalMiddleware($middleware);
        return $this;
    }

    /**
     * Sets array of global middleware
     *
     * Replaces all existing global middleware with new ones.
     *
     * @param array<int, string|callable|MiddlewareInterface> $middleware Middleware array
     * @return self Returns self for fluent interface
     *
     * @example
     * $app->setMiddleware([
     *     CorsMiddleware::class,
     *     LoggingMiddleware::class,
     *     RateLimitMiddleware::class,
     * ]);
     */
    public function setMiddleware(array $middleware): self
    {
        $this->router->setGlobalMiddleware($middleware);
        return $this;
    }

    /**
     * Gets all global middleware
     *
     * @return array<int, string|callable|MiddlewareInterface> Array of global middleware
     */
    public function getMiddleware(): array
    {
        return $this->router->getGlobalMiddleware();
    }

    /**
     * Clears all global middleware
     *
     * @return self Returns self for fluent interface
     */
    public function clearMiddleware(): self
    {
        $this->router->clearGlobalMiddleware();
        return $this;
    }

    /**
     * Runs router
     *
     * Processes current HTTP request and executes matching route.
     *
     * @return void
     *
     * @example
     * $app = new QuickRouter();
     * $app->get('/', fn() => "Hello!");
     * $app->run(); // Process request
     */
    public function run(): void
    {
        $this->router->run();
    }

    /**
     * Access to extended API
     *
     * Returns full Router for access to advanced features:
     * - Dependency Injection
     * - Caching
     * - Component configuration
     * - And much more
     *
     * @return Router Full Router with extended API
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
     * @param string|array<int, string> $methods HTTP метод(ы)
     * @param string $uri URI маршрута
     * @param callable|array<int, mixed> $handler Обработчик
     * @return RouteInterface
     * @throws \InvalidArgumentException Если обработчик имеет неверный формат
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

            if (!is_string($class) || !is_string($method)) {
                throw new \InvalidArgumentException('Handler array must contain [string $class, string $method]');
            }

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
