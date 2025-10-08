<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Router;

use FaustVik\Router\Cache\FileCache;
use FaustVik\Router\DI\DefaultContainer;
use FaustVik\Router\exceptions\NoMatch;
use FaustVik\Router\exceptions\NotAllowedHttpMethod;
use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\interfaces\Middleware\MiddlewareInterface;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Components\Config;
use FaustVik\Router\Router\Router;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для основного класса Router
 *
 * Проверяет:
 * - Базовую конфигурацию и состояние
 * - Установку коллекций и именованных маршрутов
 * - Парсинг URI и query параметров
 * - Matching маршрутов
 * - Проверку HTTP методов
 * - Кеширование
 * - DI контейнер
 * - Глобальные middleware
 */
final class RouterTest extends TestCase
{
    private Router $router;
    private RoutesCollection $routes;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->router = new Router();
        $this->routes = new RoutesCollection();
    }

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->routes = new RoutesCollection();
    }

    // ========================================================================
    // Базовая конфигурация
    // ========================================================================

    public function testConstructorCreatesDefaultConfig(): void
    {
        $router = new Router();
        $config = $router->getConfig();
        
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testSetAndGetConfig(): void
    {
        $customConfig = new Config();
        
        $this->router->setConfig($customConfig);
        
        $this->assertSame($customConfig, $this->router->getConfig());
    }

    // ========================================================================
    // Коллекции маршрутов
    // ========================================================================

    public function testSetCollection(): void
    {
        $this->routes->addGetFunc('/test', function () {
            echo 'Test';
        });

        $result = $this->router->setCollection($this->routes);
        
        // Проверяем fluent interface
        $this->assertSame($this->router, $result);
    }

    public function testSetCollectionIndexesNamedRoutes(): void
    {
        $this->routes->addGetFunc('/home', function () {
            echo 'Home';
        })->name('home');

        $this->routes->addGetFunc('/users/{id}', function ($id) {
            echo "User {$id}";
        })->name('users.show');

        $this->router->setCollection($this->routes);

        // Проверяем что именованные маршруты проиндексированы
        $this->assertTrue($this->router->has('home'));
        $this->assertTrue($this->router->has('users.show'));
        $this->assertFalse($this->router->has('non_existent'));
    }

    public function testSetCollectionReplacesNamedRoutesIndex(): void
    {
        // Первая коллекция
        $collection1 = new RoutesCollection();
        $collection1->addGetFunc('/old', function () {})->name('route1');
        $this->router->setCollection($collection1);
        
        $this->assertTrue($this->router->has('route1'));

        // Вторая коллекция (заменяет первую)
        $collection2 = new RoutesCollection();
        $collection2->addGetFunc('/new', function () {})->name('route2');
        $this->router->setCollection($collection2);

        $this->assertFalse($this->router->has('route1'));
        $this->assertTrue($this->router->has('route2'));
    }

    // ========================================================================
    // URI парсинг
    // ========================================================================

    public function testSetAndGetUri(): void
    {
        $this->router->setUri('/test/path');
        
        $this->assertEquals('/test/path', $this->router->getUri());
    }

    public function testSetUriReturnsFluentInterface(): void
    {
        $result = $this->router->setUri('/test');
        
        $this->assertSame($this->router, $result);
    }

    public function testParseExtractsQueryParameters(): void
    {
        $this->routes->addGetFunc('/search', function (Request $request) {
            $query = $request->getQuery();
            echo json_encode($query);
        });

        $this->router->setCollection($this->routes);
        $request = new Request('GET', '/search?q=test&page=2', [], ['q' => 'test', 'page' => '2']);
        
        $response = $this->router->handle($request);
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals('test', $data['q']);
        $this->assertEquals('2', $data['page']);
    }

    // ========================================================================
    // Matching маршрутов
    // ========================================================================

    public function testMatchSimpleRoute(): void
    {
        $this->routes->addGetFunc('/test', function () {
            echo 'Test';
        });

        $this->router->setCollection($this->routes);
        $request = new Request('GET', '/test');
        $response = $this->router->handle($request);

        $this->assertEquals('Test', $response->getContent());
    }

    public function testMatchParametrizedRoute(): void
    {
        $this->routes->addGetFunc('/users/{id}', function ($id) {
            echo "User: {$id}";
        });

        $this->router->setCollection($this->routes);
        $request = new Request('GET', '/users/42');
        $response = $this->router->handle($request);

        $this->assertEquals('User: 42', $response->getContent());
    }

    public function testMatchThrowsNoMatchException(): void
    {
        $this->routes->addGetFunc('/existing', function () {
            echo 'OK';
        });

        $this->router->setCollection($this->routes);
        
        $this->expectException(NoMatch::class);
        
        $request = new Request('GET', '/nonexistent');
        $this->router->handle($request);
    }

    // ========================================================================
    // Проверка HTTP методов
    // ========================================================================

    public function testCheckAllowedHttpMethod(): void
    {
        $this->routes->addGetFunc('/test', function () {
            echo 'GET';
        });

        $this->router->setCollection($this->routes);
        $request = new Request('GET', '/test');
        $response = $this->router->handle($request);

        $this->assertEquals('GET', $response->getContent());
    }

    public function testCheckThrowsNotAllowedHttpMethodException(): void
    {
        $this->routes->addGetFunc('/test', function () {
            echo 'GET only';
        });

        $this->router->setCollection($this->routes);
        
        $this->expectException(NotAllowedHttpMethod::class);
        
        $request = new Request('POST', '/test');
        $this->router->handle($request);
    }

    public function testCheckAllowsMultipleHttpMethods(): void
    {
        // Используем отдельные маршруты для разных методов на одном пути
        $this->routes->addGetFunc('/resource', function () {
            echo 'GET-OK';
        });
        $this->routes->addPostFunc('/resource-post', function () {
            echo 'POST-OK';
        });

        $this->router->setCollection($this->routes);

        // GET должен работать
        $getRequest = new Request('GET', '/resource');
        $getResponse = $this->router->handle($getRequest);
        $this->assertEquals('GET-OK', $getResponse->getContent());

        // POST должен работать на другом пути
        $postRequest = new Request('POST', '/resource-post');
        $postResponse = $this->router->handle($postRequest);
        $this->assertEquals('POST-OK', $postResponse->getContent());

        // PUT не должен работать ни на одном из маршрутов
        $this->expectException(NotAllowedHttpMethod::class);
        $putRequest = new Request('PUT', '/resource');
        $this->router->handle($putRequest);
    }

    // ========================================================================
    // Кеширование
    // ========================================================================

    public function testEnableCache(): void
    {
        $this->router->enableCache();
        
        // После enableCache() кеширование должно быть активно
        $this->assertTrue($this->router->isCacheEnabled());
    }

    public function testDisableCache(): void
    {
        $this->router->enableCache();
        $this->router->disableCache();
        
        $this->assertFalse($this->router->isCacheEnabled());
    }

    public function testCacheEnabledByDefault(): void
    {
        $router = new Router();
        
        // По умолчанию кеш должен быть отключен
        $this->assertFalse($router->isCacheEnabled());
    }

    // ========================================================================
    // DI контейнер
    // ========================================================================

    public function testSetContainer(): void
    {
        $container = new DefaultContainer();
        
        $this->router->setContainer($container);
        
        $this->assertSame($container, $this->router->getContainer());
    }

    public function testGetContainerReturnsNullByDefault(): void
    {
        $router = new Router();
        
        $this->assertNull($router->getContainer());
    }

    // ========================================================================
    // Глобальные middleware
    // ========================================================================

    public function testAddGlobalMiddleware(): void
    {
        $middleware = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                $response = $next($request);
                return $response->withHeader('X-Test', 'true');
            }
        };

        $result = $this->router->addGlobalMiddleware($middleware);
        
        // Проверяем fluent interface
        $this->assertSame($this->router, $result);
        
        // Проверяем что middleware добавлен
        $globalMiddleware = $this->router->getGlobalMiddleware();
        $this->assertCount(1, $globalMiddleware);
    }

    public function testGlobalMiddlewareIsExecuted(): void
    {
        $middleware = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                $response = $next($request);
                return $response->withHeader('X-Global', 'executed');
            }
        };

        $this->router->addGlobalMiddleware($middleware);

        $this->routes->addGetFunc('/test', function () {
            echo 'Test';
        });

        $this->router->setCollection($this->routes);
        $request = new Request('GET', '/test');
        $response = $this->router->handle($request);

        $this->assertEquals('executed', $response->getHeader('X-Global'));
    }

    public function testSetGlobalMiddleware(): void
    {
        $middleware1 = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response { return $next($request); }
        };
        $middleware2 = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response { return $next($request); }
        };

        $result = $this->router->setGlobalMiddleware([$middleware1, $middleware2]);
        
        $this->assertSame($this->router, $result);
        
        $globalMiddleware = $this->router->getGlobalMiddleware();
        $this->assertCount(2, $globalMiddleware);
    }

    public function testSetGlobalMiddlewareReplacesExisting(): void
    {
        $middleware1 = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response { return $next($request); }
        };
        $middleware2 = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response { return $next($request); }
        };
        
        $this->router->addGlobalMiddleware($middleware1);
        $this->router->addGlobalMiddleware($middleware2);
        
        $this->assertCount(2, $this->router->getGlobalMiddleware());

        $middleware3 = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response { return $next($request); }
        };
        $this->router->setGlobalMiddleware([$middleware3]);
        
        $this->assertCount(1, $this->router->getGlobalMiddleware());
    }

    public function testGetGlobalMiddleware(): void
    {
        $middleware = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response { return $next($request); }
        };
        
        $this->router->addGlobalMiddleware($middleware);
        
        $globalMiddleware = $this->router->getGlobalMiddleware();
        
        $this->assertIsArray($globalMiddleware);
        $this->assertCount(1, $globalMiddleware);
        $this->assertSame($middleware, $globalMiddleware[0]);
    }

    public function testClearGlobalMiddleware(): void
    {
        $middleware1 = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response { return $next($request); }
        };
        $middleware2 = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response { return $next($request); }
        };
        
        $this->router->addGlobalMiddleware($middleware1);
        $this->router->addGlobalMiddleware($middleware2);
        
        $this->assertCount(2, $this->router->getGlobalMiddleware());

        $result = $this->router->clearGlobalMiddleware();
        
        $this->assertSame($this->router, $result);
        $this->assertCount(0, $this->router->getGlobalMiddleware());
    }

    public function testGlobalMiddlewareExecutionOrder(): void
    {
        $order = [];

        $middleware1 = new class($order) implements MiddlewareInterface {
            public function __construct(private array &$order) {}
            public function handle(Request $request, callable $next): Response
            {
                $this->order[] = 'global1';
                return $next($request);
            }
        };

        $middleware2 = new class($order) implements MiddlewareInterface {
            public function __construct(private array &$order) {}
            public function handle(Request $request, callable $next): Response
            {
                $this->order[] = 'global2';
                return $next($request);
            }
        };

        $this->router->addGlobalMiddleware($middleware1);
        $this->router->addGlobalMiddleware($middleware2);

        $this->routes->addGetFunc('/test', function () use (&$order) {
            $order[] = 'handler';
            echo 'Test';
        });

        $this->router->setCollection($this->routes);
        $request = new Request('GET', '/test');
        $this->router->handle($request);

        // Глобальные middleware выполняются в порядке добавления перед handler
        $this->assertEquals(['global1', 'global2', 'handler'], $order);
    }

    // ========================================================================
    // Интеграционные тесты
    // ========================================================================

    public function testCompleteRequestLifecycle(): void
    {
        // Настраиваем роутер с полным стеком
        $container = new DefaultContainer();
        $this->router->setContainer($container);

        $globalMiddleware = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                $response = $next($request);
                return $response->withHeader('X-Powered-By', 'Router');
            }
        };
        $this->router->addGlobalMiddleware($globalMiddleware);

        $this->routes->addGetFunc('/users/{id}', function ($id) {
            echo "User ID: {$id}";
        })->name('users.show');

        $this->router->setCollection($this->routes);

        // Выполняем запрос
        $request = new Request('GET', '/users/123');
        $response = $this->router->handle($request);

        // Проверяем результат
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('User ID: 123', $response->getContent());
        $this->assertEquals('Router', $response->getHeader('X-Powered-By'));
    }

    public function testMultipleRoutesWithDifferentMethods(): void
    {
        // Создаем отдельные маршруты для разных методов
        $this->routes->addGetFunc('/resource-get', function () {
            echo 'GET';
        });
        $this->routes->addPostFunc('/resource-post', function () {
            echo 'POST';
        });
        $this->routes->addPutFunc('/resource-put', function () {
            echo 'PUT';
        });
        $this->routes->addDeleteFunc('/resource-delete', function () {
            echo 'DELETE';
        });

        $this->router->setCollection($this->routes);

        $tests = [
            ['method' => 'GET', 'uri' => '/resource-get', 'expected' => 'GET'],
            ['method' => 'POST', 'uri' => '/resource-post', 'expected' => 'POST'],
            ['method' => 'PUT', 'uri' => '/resource-put', 'expected' => 'PUT'],
            ['method' => 'DELETE', 'uri' => '/resource-delete', 'expected' => 'DELETE'],
        ];

        foreach ($tests as $test) {
            $request = new Request($test['method'], $test['uri']);
            $response = $this->router->handle($request);
            $this->assertEquals($test['expected'], $response->getContent());
        }
    }

    public function testNamedRoutesAccessibility(): void
    {
        $this->routes->addGetFunc('/home', function () {})->name('home');
        $this->routes->addGetFunc('/about', function () {})->name('about');
        $this->routes->addGetFunc('/no-name', function () {});

        $this->router->setCollection($this->routes);

        $namedRoutes = $this->router->getNamedRoutes();

        $this->assertArrayHasKey('home', $namedRoutes);
        $this->assertArrayHasKey('about', $namedRoutes);
        $this->assertArrayNotHasKey('no-name', $namedRoutes);
    }
}

