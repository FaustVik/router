<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Router;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Interfaces\Middleware\MiddlewareInterface;
use FaustVik\Router\Router\QuickRouter;
use FaustVik\Router\Router\Router;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Тесты для QuickRouter API
 *
 * Проверяет:
 * - Инициализацию и конфигурацию
 * - HTTP методы (get, post, put, delete, patch, any, match)
 * - Группы маршрутов (prefix, middleware)
 * - Глобальные middleware
 * - Доступ к продвинутому API
 */
final class QuickRouterTest extends TestCase
{
    // ========================================================================
    // Инициализация
    // ========================================================================

    public function testConstructorWithDefaults(): void
    {
        $app = new QuickRouter();

        // По умолчанию кеш и DI отключены
        $this->assertFalse($app->advanced()->isCacheEnabled());
        $this->assertNull($app->advanced()->getContainer());
    }

    public function testConstructorWithCacheEnabled(): void
    {
        $app = new QuickRouter(cache: true);

        $this->assertTrue($app->advanced()->isCacheEnabled());
    }

    public function testConstructorWithDiEnabled(): void
    {
        $app = new QuickRouter(di: true);

        $this->assertNotNull($app->advanced()->getContainer());
    }

    public function testConstructorWithBothOptions(): void
    {
        $app = new QuickRouter(cache: true, di: true);

        $this->assertTrue($app->advanced()->isCacheEnabled());
        $this->assertNotNull($app->advanced()->getContainer());
    }

    public function testConstructorWithArrayOptions(): void
    {
        // Старый стиль (обратная совместимость)
        $app = new QuickRouter(['cache' => true, 'di' => true]);

        $this->assertTrue($app->advanced()->isCacheEnabled());
        $this->assertNotNull($app->advanced()->getContainer());
    }

    // ========================================================================
    // HTTP методы
    // ========================================================================

    public function testGetRoute(): void
    {
        $app = new QuickRouter();
        $route = $app->get('/test', function (): void {
            echo 'GET test';
        });

        $this->assertNotNull($route);
        $this->assertEquals('/test', $route->getRoute());

        $request = new Request('GET', '/test');
        $response = $app->advanced()->handle($request);
        $this->assertEquals('GET test', $response->getContent());
    }

    public function testPostRoute(): void
    {
        $app = new QuickRouter();
        $route = $app->post('/test', function (): void {
            echo 'POST test';
        });

        $this->assertNotNull($route);
        $this->assertEquals('/test', $route->getRoute());

        $request = new Request('POST', '/test');
        $response = $app->advanced()->handle($request);
        $this->assertEquals('POST test', $response->getContent());
    }

    public function testPutRoute(): void
    {
        $app = new QuickRouter();
        $route = $app->put('/test', function (): void {
            echo 'PUT test';
        });

        $this->assertNotNull($route);

        $request = new Request('PUT', '/test');
        $response = $app->advanced()->handle($request);
        $this->assertEquals('PUT test', $response->getContent());
    }

    public function testDeleteRoute(): void
    {
        $app = new QuickRouter();
        $route = $app->delete('/test', function (): void {
            echo 'DELETE test';
        });

        $this->assertNotNull($route);

        $request = new Request('DELETE', '/test');
        $response = $app->advanced()->handle($request);
        $this->assertEquals('DELETE test', $response->getContent());
    }

    public function testPatchRoute(): void
    {
        $app = new QuickRouter();
        $route = $app->patch('/test', function (): void {
            echo 'PATCH test';
        });

        $this->assertNotNull($route);

        $request = new Request('PATCH', '/test');
        $response = $app->advanced()->handle($request);
        $this->assertEquals('PATCH test', $response->getContent());
    }

    public function testAnyRoute(): void
    {
        $app = new QuickRouter();
        $app->any('/webhook', function (): void {
            echo 'ANY method';
        });

        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

        foreach ($methods as $method) {
            $request = new Request($method, '/webhook');
            $response = $app->advanced()->handle($request);
            $this->assertEquals('ANY method', $response->getContent(), "Failed for method: {$method}");
        }
    }

    public function testMatchRoute(): void
    {
        $app = new QuickRouter();
        $app->match(['GET', 'POST'], '/form', function (): void {
            echo 'Form handler';
        });

        // GET должен работать
        $getRequest = new Request('GET', '/form');
        $getResponse = $app->advanced()->handle($getRequest);
        $this->assertEquals('Form handler', $getResponse->getContent());

        // POST должен работать
        $postRequest = new Request('POST', '/form');
        $postResponse = $app->advanced()->handle($postRequest);
        $this->assertEquals('Form handler', $postResponse->getContent());
    }

    public function testRouteWithParameters(): void
    {
        $app = new QuickRouter();
        $app->get('/users/{id}', function ($id): void {
            echo "User: {$id}";
        });

        $request = new Request('GET', '/users/42');
        $response = $app->advanced()->handle($request);
        $this->assertEquals('User: 42', $response->getContent());
    }

    public function testRouteWithMultipleParameters(): void
    {
        $app = new QuickRouter();
        $app->get('/posts/{id}/comments/{commentId}', function ($id, $commentId): void {
            echo "Post: {$id}, Comment: {$commentId}";
        });

        $request = new Request('GET', '/posts/10/comments/5');
        $response = $app->advanced()->handle($request);
        $this->assertEquals('Post: 10, Comment: 5', $response->getContent());
    }

    public function testRouteWithControllerArray(): void
    {
        $app = new QuickRouter();
        $controller = new class () {
            public function index(): void
            {
                echo 'Controller index';
            }
        };

        $app->get('/controller', [$controller::class, 'index']);

        $request = new Request('GET', '/controller');
        $response = $app->advanced()->handle($request);
        $this->assertEquals('Controller index', $response->getContent());
    }

    public function testRouteWithNamedRoute(): void
    {
        $app = new QuickRouter();
        $route = $app->get('/home', function (): void {
            echo 'Home';
        });
        $route->name('home');

        // Проверяем что маршрут зарегистрирован
        $request = new Request('GET', '/home');
        $response = $app->advanced()->handle($request);
        $this->assertEquals('Home', $response->getContent());

        // Проверяем что имя маршрута установлено
        $this->assertEquals('home', $route->getName());
    }

    // ========================================================================
    // Группы маршрутов
    // ========================================================================

    public function testPrefixGroup(): void
    {
        $app = new QuickRouter();

        // Создаем контроллеры для тестирования
        $usersController = new class () {
            public function index(): void
            {
                echo 'Users API';
            }
        };
        $postsController = new class () {
            public function index(): void
            {
                echo 'Posts API';
            }
        };

        $result = $app->prefix('/api', function ($group) use ($usersController, $postsController): void {
            $group->get('/users', $usersController::class, 'index');
            $group->get('/posts', $postsController::class, 'index');
        });

        // Проверяем fluent interface
        $this->assertSame($app, $result);

        // Проверяем что маршруты созданы с префиксом
        $usersRequest = new Request('GET', '/api/users');
        $usersResponse = $app->advanced()->handle($usersRequest);
        $this->assertEquals('Users API', $usersResponse->getContent());

        $postsRequest = new Request('GET', '/api/posts');
        $postsResponse = $app->advanced()->handle($postsRequest);
        $this->assertEquals('Posts API', $postsResponse->getContent());
    }

    public function testMiddlewareGroup(): void
    {
        $app = new QuickRouter();

        $testMiddleware = new class () implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                $response = $next($request);
                return $response->withHeader('X-Group', 'Protected');
            }
        };

        $controller = new class () {
            public function index(): void
            {
                echo 'Admin area';
            }
        };

        $result = $app->middleware([$testMiddleware], function ($group) use ($controller): void {
            $group->get('/admin', $controller::class, 'index');
        });

        $this->assertSame($app, $result);

        // Проверяем что группа создана и маршрут работает
        $request = new Request('GET', '/admin');
        $response = $app->advanced()->handle($request);
        $this->assertEquals('Admin area', $response->getContent());
        $this->assertEquals('Protected', $response->getHeader('X-Group'));
    }

    // ========================================================================
    // Глобальные middleware
    // ========================================================================

    public function testAddMiddleware(): void
    {
        $app = new QuickRouter();

        $middleware = new class () implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                $response = $next($request);
                return $response->withHeader('X-Global', 'Middleware');
            }
        };

        $result = $app->addMiddleware($middleware);

        // Fluent interface
        $this->assertSame($app, $result);

        // Middleware добавлен
        $this->assertCount(1, $app->getMiddleware());

        // Middleware выполняется
        $app->get('/test', function (): void {
            echo 'Test';
        });

        $request = new Request('GET', '/test');
        $response = $app->advanced()->handle($request);
        $this->assertEquals('Middleware', $response->getHeader('X-Global'));
    }

    public function testSetMiddleware(): void
    {
        $app = new QuickRouter();

        $middleware1 = new class () implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request);
            }
        };
        $middleware2 = new class () implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request);
            }
        };

        $result = $app->setMiddleware([$middleware1, $middleware2]);

        $this->assertSame($app, $result);
        $this->assertCount(2, $app->getMiddleware());
    }

    public function testGetMiddleware(): void
    {
        $app = new QuickRouter();

        $this->assertIsArray($app->getMiddleware());
        $this->assertCount(0, $app->getMiddleware());

        $middleware = new class () implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request);
            }
        };
        $app->addMiddleware($middleware);

        $this->assertCount(1, $app->getMiddleware());
    }

    public function testClearMiddleware(): void
    {
        $app = new QuickRouter();

        $middleware = new class () implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request);
            }
        };

        $app->addMiddleware($middleware);
        $this->assertCount(1, $app->getMiddleware());

        $result = $app->clearMiddleware();

        $this->assertSame($app, $result);
        $this->assertCount(0, $app->getMiddleware());
    }

    public function testMultipleMiddlewareExecution(): void
    {
        $app = new QuickRouter();

        $order = [];

        $middleware1 = new class ($order) implements MiddlewareInterface {
            public function __construct(private array &$order)
            {
            }
            public function handle(Request $request, callable $next): Response
            {
                $this->order[] = 'middleware1';
                return $next($request);
            }
        };

        $middleware2 = new class ($order) implements MiddlewareInterface {
            public function __construct(private array &$order)
            {
            }
            public function handle(Request $request, callable $next): Response
            {
                $this->order[] = 'middleware2';
                return $next($request);
            }
        };

        $app->addMiddleware($middleware1);
        $app->addMiddleware($middleware2);

        $app->get('/test', function () use (&$order): void {
            $order[] = 'handler';
            echo 'Test';
        });

        $request = new Request('GET', '/test');
        $app->advanced()->handle($request);

        $this->assertEquals(['middleware1', 'middleware2', 'handler'], $order);
    }

    // ========================================================================
    // Доступ к продвинутому API
    // ========================================================================

    public function testAdvancedReturnsRouter(): void
    {
        $app = new QuickRouter();

        $router = $app->advanced();

        $this->assertInstanceOf(Router::class, $router);
    }

    public function testAdvancedApiAccess(): void
    {
        $app = new QuickRouter();

        // Через advanced() можем использовать полный API Router
        $app->advanced()->enableCache();
        $this->assertTrue($app->advanced()->isCacheEnabled());

        $app->advanced()->disableCache();
        $this->assertFalse($app->advanced()->isCacheEnabled());
    }

    // ========================================================================
    // Интеграционные тесты
    // ========================================================================

    public function testCompleteApplication(): void
    {
        $app = new QuickRouter();

        // Глобальный middleware
        $app->addMiddleware(new class () implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                $response = $next($request);
                return $response->withHeader('X-App', 'QuickRouter');
            }
        });

        // Маршруты
        $app->get('/', function (): void {
            echo 'Home';
        });

        $userRoute = $app->get('/users/{id}', function ($id): void {
            echo "User: {$id}";
        });
        $userRoute->name('users.show');

        $app->post('/users', function (): void {
            echo 'Create user';
        });

        // Тестируем
        $homeRequest = new Request('GET', '/');
        $homeResponse = $app->advanced()->handle($homeRequest);
        $this->assertEquals('Home', $homeResponse->getContent());
        $this->assertEquals('QuickRouter', $homeResponse->getHeader('X-App'));

        $userRequest = new Request('GET', '/users/123');
        $userResponse = $app->advanced()->handle($userRequest);
        $this->assertEquals('User: 123', $userResponse->getContent());

        $createRequest = new Request('POST', '/users');
        $createResponse = $app->advanced()->handle($createRequest);
        $this->assertEquals('Create user', $createResponse->getContent());

        // Проверяем что имя маршрута установлено
        $this->assertEquals('users.show', $userRoute->getName());
    }

    public function testFluentInterface(): void
    {
        $app = new QuickRouter();

        $middleware1 = new class () implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request);
            }
        };
        $middleware2 = new class () implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request);
            }
        };

        // Цепочка вызовов
        $result = $app
            ->addMiddleware($middleware1)
            ->addMiddleware($middleware2);

        $this->assertSame($app, $result);
        $this->assertCount(2, $app->getMiddleware());

        $result = $app->clearMiddleware();
        $this->assertSame($app, $result);
        $this->assertCount(0, $app->getMiddleware());
    }

    public function testInvalidHandlerThrowsException(): void
    {
        $this->expectException(TypeError::class);

        $app = new QuickRouter();

        // @phpstan-ignore-next-line - Намеренная ошибка для теста
        $app->get('/test', 'invalid_handler');
    }
}
