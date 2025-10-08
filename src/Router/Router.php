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
 * Main Router class
 *
 * Provides:
 * - HTTP request routing
 * - URL and query string parameter handling
 * - Middleware support
 * - Route matching caching
 * - Dependency Injection container
 *
 * @package FaustVik\Router\Router
 */
final class Router implements RouterInterface, CacheableRouterInterface
{
    private ?string $uriRaw = null;
    private ?string $uri = null;
    private ?string $paramsString = null;

    /**
     * @var array<string, mixed>|null Query parameters from URI
     * @phpstan-ignore-next-line property.onlyWritten
     */
    private ?array $params = null;

    private ConfigInterface $config;
    private ?RoutesCollectionInterface $collections = null;
    private ?RouterContainerInterface $container = null;

    /** @var array<string, RouteInterface> Indexed named routes */
    private array $namedRoutes = [];

    /** @var array<int, string|callable> Global middleware for all routes */
    private array $globalMiddleware = [];

    /**
     * Router constructor
     *
     * Initializes basic router configuration
     */
    public function __construct()
    {
        $this->config = new Config();
    }

    /**
     * Sets router configuration
     */
    public function setConfig(ConfigInterface $config): void
    {
        $this->config = $config;
    }

    /**
     * Gets current router configuration
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * Sets routes collection
     *
     * Automatically indexes named routes for fast access
     */
    public function setCollection(RoutesCollectionInterface $collections): self
    {
        $this->collections = $collections;

        // Index named routes for fast access through url()
        $this->namedRoutes = [];
        foreach ($collections->get() as $route) {
            if ($route->getName()) {
                $this->namedRoutes[$route->getName()] = $route;
            }
        }

        return $this;
    }

    /**
     * Processes Request and returns Response without sending
     *
     * This method executes all routing logic but doesn't send response to client.
     * Useful for:
     * - Route testing
     * - Building custom HTTP servers
     * - Integration with other frameworks
     * - Getting response for further processing
     *
     * Full processing cycle:
     * 1. Parse URI to extract path and parameters
     * 2. Find matching route
     * 3. Check HTTP method
     * 4. Execute middleware stack (global + route-specific)
     * 5. Run route handler
     * 6. Return Response object
     *
     * @param Request $request HTTP request to process
     * @return Response HTTP response
     * @throws \FaustVik\Router\exceptions\NoMatch If route not found
     * @throws \FaustVik\Router\exceptions\NotAllowedHttpMethod If HTTP method not allowed
     *
     * @example
     * // Route testing
     * $request = new Request('GET', '/users/123');
     * $response = $router->handle($request);
     * assert($response->getStatusCode() === 200);
     *
     * @example
     * // Integration with another framework
     * $response = $router->handle($psrRequest->toRequest());
     * return $response->toPsr7Response();
     */
    public function handle(Request $request): Response
    {
        // Set URI from request
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
     * Main router execution method
     *
     * Convenience method for quick start. Creates Request from global variables,
     * processes it through handle() and sends response to client.
     *
     * For testing and more flexible control use handle() directly.
     *
     * @throws \FaustVik\Router\exceptions\NoMatch If route not found
     * @throws \FaustVik\Router\exceptions\NotAllowedHttpMethod If HTTP method not allowed
     *
     * @example
     * // Common usage
     * $router = new Router();
     * $router->setCollection($routes);
     * $router->run();
     *
     * @see handle() For processing without automatic response sending
     */
    public function run(): void
    {
        // Create Request from PHP global variables
        $request = Request::createFromGlobals();

        // Process request through handle()
        $response = $this->handle($request);

        // Send response to client
        $response->send();
    }

    /**
     * Sets URI for processing
     *
     * Used for testing or when need to process
     * specific URI not from $_SERVER
     */
    public function setUri(string $uri): self
    {
        $this->uriRaw = $uri;
        return $this;
    }

    /**
     * Gets URI for processing
     *
     * If URI not explicitly set, takes from $_SERVER['REQUEST_URI']
     */
    public function getUri(): string
    {
        if (!$this->uriRaw) {
            $this->uriRaw = $_SERVER['REQUEST_URI'] ?? '/';
        }

        return $this->uriRaw;
    }

    /**
     * Parses request URI
     *
     * Splits URI into path and query string parameters.
     * Uses parse_url() and parse_str() for proper complex URL handling.
     *
     * Supports:
     * - Arrays in query string: ?ids[]=1&ids[]=2
     * - Nested parameters: ?user[name]=John&user[age]=30
     * - Special characters in parameters
     *
     * Example: "/users/123?name=John&age=30" ->
     * - $this->uri = "/users/123"
     * - $this->params = ["name" => "John", "age" => "30"]
     */
    protected function parse(): void
    {
        $uri = $this->getUri();

        // Use parse_url() for correct URI parsing
        // This is faster and more reliable than manual parsing
        $parsed = parse_url($uri);

        // Extract path and decode it
        $this->uri = isset($parsed['path']) ? urldecode($parsed['path']) : '/';

        // Save params string for backward compatibility
        $this->paramsString = $parsed['query'] ?? null;

        // Parse query string using parse_str()
        // This correctly handles arrays and nested parameters
        if ($this->paramsString) {
            $parsedParams = [];
            parse_str($this->paramsString, $parsedParams);
            // @var array<string, mixed> $parsedParams

            $this->params = $parsedParams;
        }
    }

    /**
     * Finds matching route for URI
     *
     * Uses Matching component to find route
     * that matches current URI.
     *
     * @throws \FaustVik\Router\exceptions\NoMatch If matching route not found
     */
    protected function match(): MatchResult
    {
        // After parse() call $this->uri is always initialized
        assert($this->uri !== null);

        return $this->getConfig()->getMatch()->match($this->uri, $this->collections);
    }

    /**
     * Checks allowed HTTP methods for route
     *
     * Uses CheckerHttpMethod component to verify
     * if current HTTP method is allowed for found route.
     *
     * @throws \FaustVik\Router\exceptions\NotAllowedHttpMethod If HTTP method not allowed
     */

    /**
     * Checks if HTTP method is allowed for route
     *
     * @param RouteInterface $route Route to check
     * @param string|null $httpMethod HTTP method (if null, taken from $_SERVER)
     * @throws \FaustVik\Router\exceptions\NotAllowedHttpMethod
     */
    protected function check(RouteInterface $route, ?string $httpMethod = null): void
    {
        $this->getConfig()->getCheckerHttpMethod()->isAllow($route->getMethods(), $httpMethod);
    }

    /**
     * Enables route caching
     *
     * Activates caching system to speed up route matching.
     * Route matching results will be saved in cache.
     */
    public function enableCache(): void
    {
        $this->config->enableCache();
    }

    /**
     * Disables route caching
     *
     * Deactivates caching system.
     * Route matching will be performed from scratch each time.
     */
    public function disableCache(): void
    {
        $this->config->disableCache();
    }

    /**
     * Checks if caching is enabled
     */
    public function isCacheEnabled(): bool
    {
        return $this->config->isCacheEnabled();
    }

    /**
     * Sets cache driver
     */
    public function setCache(CacheInterface $cache): void
    {
        $this->config->setCache($cache);
    }

    /**
     * Gets current cache driver
     */
    public function getCache(): ?CacheInterface
    {
        return $this->config->getCache();
    }

    /**
     * Clears route cache
     *
     * Removes all saved route matching results.
     */
    public function clearRouteCache(): bool
    {
        return $this->config->clearCache();
    }

    /**
     * Generates cache key for current URI
     */
    public function getCacheKey(): string
    {
        return 'router_cache_' . md5($this->getUri());
    }

    // ============================================================================
    // DI Container methods - Methods for working with Dependency Injection container
    // ============================================================================

    /**
     * Enables Dependency Injection with optional configuration
     *
     * Creates built-in DefaultContainer and configures it
     * using passed closure function.
     *
     * @param \Closure|null $configurator Function to configure container
     * @return self Returns current instance for method chaining
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

        // Update configuration with container
        $this->config->setContainer($this->container);

        return $this;
    }

    /**
     * Sets custom container
     *
     * Allows using external container (Symfony, PHP-DI, Pimple, etc.)
     * through adapter system.
     *
     * @param object $container External container instance
     * @return self Returns current instance for method chaining
     *
     * @example
     * $phpDiContainer = new DI\Container();
     * $router->setContainer($phpDiContainer);
     */
    public function setContainer(object $container): self
    {
        $this->container = ContainerAdapterFactory::createFor($container);

        // Update configuration with container
        $this->config->setContainer($this->container);

        return $this;
    }

    /**
     * Gets current DI container
     */
    public function getContainer(): ?RouterContainerInterface
    {
        return $this->container;
    }

    /**
     * Checks if DI is enabled
     */
    public function isDIEnabled(): bool
    {
        return $this->container !== null;
    }

    /**
     * Configures container using configuration array
     *
     * Allows configuring container declaratively through array.
     * Supports 'bindings' and 'singletons' sections.
     *
     * @param array $config Container configuration array
     * @return self Returns current instance for method chaining
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
     * @throws \InvalidArgumentException If binding/singleton key is not a string
     */
    public function configureContainer(array $config): self
    {
        if (!$this->container) {
            $this->container = new DefaultContainer();
            $this->config->setContainer($this->container);
        }

        // Process regular bindings
        if (isset($config['bindings']) && is_array($config['bindings'])) {
            foreach ($config['bindings'] as $abstract => $concrete) {
                if (!is_string($abstract)) {
                    throw new \InvalidArgumentException('Binding key must be a string');
                }
                $this->container->bind($abstract, $concrete);
            }
        }

        // Process singleton bindings
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
     * Binds service to container
     *
     * Registers binding of abstraction to concrete implementation.
     * New instance will be created on each request.
     *
     * @param string $abstract Abstraction (interface or class)
     * @param mixed $concrete Concrete implementation (class, closure or instance)
     * @return self Returns current instance for method chaining
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
     * Binds singleton to container
     *
     * Registers binding of abstraction to concrete implementation as singleton.
     * Instance will be created only once and reused.
     *
     * @param string $abstract Abstraction (interface or class)
     * @param mixed $concrete Concrete implementation (class, closure or instance)
     * @return self Returns current instance for method chaining
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
    // Named Routes & URL Generation - Named routes and URL generation
    // ============================================================================

    /**
     * Generates URL by route name
     *
     * Replaces parameters in URL and removes optional parameters
     * that were not provided. Supports query parameters and fragments.
     *
     * @param string $name Route name
     * @param array<string, string|int> $params Path parameters for substitution in {placeholders}
     * @param array<string, string|int|bool|array<mixed>> $query Query parameters to add to URL (?key=value)
     * @param string|null $fragment Anchor/fragment to add to URL (#fragment)
     * @return string Generated URL
     * @throws \InvalidArgumentException If route not found or not all required parameters provided
     * @throws \RuntimeException If URL generation fails
     *
     * @example
     * // Basic example
     * $router->url('users.show', ['id' => 123]);
     * // => /users/123
     *
     * @example
     * // With named arguments (recommended for complex URLs)
     * $router->url(
     *     name: 'posts.show',
     *     params: ['id' => 456],
     *     query: ['ref' => 'home', 'utm_source' => 'newsletter'],
     *     fragment: 'comments'
     * );
     * // => /posts/456?ref=home&utm_source=newsletter#comments
     *
     * @example
     * // With query parameters
     * $router->url('users.index', [], ['page' => 2, 'sort' => 'name']);
     * // => /users?page=2&sort=name
     *
     * @example
     * // With fragment
     * $router->url('posts.show', ['id' => 456], [], 'comments');
     * // => /posts/456#comments
     *
     * @example
     * // Optional parameters
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

        // Validate parameters against constraints before URL generation
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

        // Replace parameters in URI
        // Support formats: {param}, {param?}, {param:pattern}, {param:pattern?}
        foreach ($params as $key => $value) {
            // Escape value for security
            $escapedValue = rawurlencode((string) $value);

            // Replace all parameter variants
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

            // preg_replace can return null on error
            if ($replaced === null) {
                throw new \RuntimeException("Failed to replace parameter '{$key}' in URI");
            }
            $uri = $replaced;
        }

        // Remove remaining optional parameters
        $uri = preg_replace('/\{[^}]+\?\}/', '', $uri);
        if ($uri === null) {
            throw new \RuntimeException("Failed to process optional parameters in URI");
        }

        $uri = preg_replace('/\{[^}]+:[^}]+\?\}/', '', $uri);
        if ($uri === null) {
            throw new \RuntimeException("Failed to process optional parameters in URI");
        }

        // Check that all required parameters were filled
        if (preg_match('/\{([^}?:]+)(?::[^}]+)?\}/', $uri, $matches)) {
            throw new \InvalidArgumentException(
                "Missing required parameter '{$matches[1]}' for route '{$name}'"
            );
        }

        // Clean up double slashes and trailing slash
        $uri = preg_replace('#/{2,}#', '/', $uri);
        if ($uri === null) {
            throw new \RuntimeException("Failed to clean up URI slashes");
        }

        $uri = rtrim($uri, '/');

        // Return root path if URI is empty
        $uri = $uri === '' ? '/' : $uri;

        // Add query parameters if present
        if (!empty($query)) {
            $uri .= '?' . http_build_query($query);
        }

        // Add fragment if present
        if ($fragment !== null && $fragment !== '') {
            $uri .= '#' . rawurlencode($fragment);
        }

        return $uri;
    }

    /**
     * Checks if named route exists
     *
     * @param string $name Route name
     * @return bool True if route exists
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
     * Gets all named routes
     *
     * @return array<string, RouteInterface> Associative array [name => RouteInterface]
     */
    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }

    /**
     * Gets route by name
     *
     * @param string $name Route name
     * @return RouteInterface|null Route or null if not found
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
    // Global Middleware - Global middleware for all routes
    // ============================================================================

    /**
     * Adds global middleware for all routes
     *
     * Global middleware executes before route-specific middleware.
     * Useful for CORS, logging, authentication and other common tasks.
     *
     * @param string|callable $middleware Middleware class or callable
     * @return self Returns current instance for method chaining
     *
     * @example
     * // Adding middleware class
     * $router->addGlobalMiddleware(CorsMiddleware::class);
     *
     * @example
     * // Adding middleware function
     * $router->addGlobalMiddleware(fn($request, $next) => $next($request));
     *
     * @example
     * // Method chaining (recommended)
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
     * Sets array of global middleware
     *
     * Replaces all existing global middleware with new ones.
     *
     * @param array<int, string|callable> $middleware Middleware array
     * @return self Returns current instance for method chaining
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
     * Gets all global middleware
     *
     * @return array<int, string|object|callable> Array of global middleware
     */
    public function getGlobalMiddleware(): array
    {
        return $this->globalMiddleware;
    }

    /**
     * Clears all global middleware
     *
     * @return self Returns current instance for method chaining
     */
    public function clearGlobalMiddleware(): self
    {
        $this->globalMiddleware = [];
        return $this;
    }
}
