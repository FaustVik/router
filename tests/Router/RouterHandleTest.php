<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Router;

use FaustVik\Router\exceptions\NoMatch;
use FaustVik\Router\exceptions\NotAllowedHttpMethod;
use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для метода Router::handle()
 *
 * Проверяет обработку запросов без автоматической отправки ответа
 */
final class RouterHandleTest extends TestCase
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
        $this->router->setCollection($this->routes);
    }

    public function testHandleReturnsResponse(): void
    {
        $this->routes->addGetFunc('/test', function () {
            echo 'Hello World';
        });

        $request = new Request('GET', '/test');
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello World', $response->getContent());
    }

    public function testHandleWithParameters(): void
    {
        $this->routes->addGetFunc('/users/{id}', function ($id) {
            echo "User ID: {$id}";
        });

        $request = new Request('GET', '/users/123');
        $response = $this->router->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('User ID: 123', $response->getContent());
    }

    public function testHandleWithMultipleParameters(): void
    {
        $this->routes->addGetFunc('/users/{userId}/posts/{postId}', function ($userId, $postId) {
            echo "User {$userId}, Post {$postId}";
        });

        $request = new Request('GET', '/users/42/posts/99');
        $response = $this->router->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('User 42, Post 99', $response->getContent());
    }

    public function testHandleWithPostData(): void
    {
        $this->routes->addPostFunc('/users', function (Request $request) {
            $name = $request->input('name');
            echo "Created user: {$name}";
        });

        $request = (new Request('POST', '/users'))
            ->withBody(['name' => 'John Doe']);
        $response = $this->router->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Created user: John Doe', $response->getContent());
    }

    public function testHandleThrowsNoMatchException(): void
    {
        $this->routes->addGetFunc('/existing', function () {
            echo 'OK';
        });

        $this->expectException(NoMatch::class);

        $request = new Request('GET', '/nonexistent');
        $this->router->handle($request);
    }

    public function testHandleThrowsNotAllowedHttpMethodException(): void
    {
        $this->routes->addGetFunc('/users', function () {
            echo 'Users list';
        });

        $this->expectException(NotAllowedHttpMethod::class);

        $request = new Request('POST', '/users');
        $this->router->handle($request);
    }

    public function testHandleWithEchoResponse(): void
    {
        $this->routes->addGetFunc('/json', function () {
            echo json_encode(['status' => 'success', 'data' => ['id' => 1]]);
        });

        $request = new Request('GET', '/json');
        $response = $this->router->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('success', $response->getContent());
    }

    public function testHandleDoesNotSendResponse(): void
    {
        $this->routes->addGetFunc('/test', function () {
            echo 'Test Content';
        });

        $request = new Request('GET', '/test');
        
        // Начинаем output buffering чтобы поймать любой вывод
        ob_start();
        $response = $this->router->handle($request);
        $output = ob_get_clean();

        // handle() не должен ничего выводить напрямую (все захватывается в Response)
        $this->assertEmpty($output);
        
        // Но Response должен содержать контент
        $this->assertEquals('Test Content', $response->getContent());
    }

    public function testHandleWithDifferentHttpMethods(): void
    {
        // Создаем отдельные маршруты для каждого метода
        $this->routes->addGetFunc('/get-resource', function () {
            echo 'GET';
        });
        $this->routes->addPostFunc('/post-resource', function () {
            echo 'POST';
        });
        $this->routes->addPutFunc('/put-resource', function () {
            echo 'PUT';
        });
        $this->routes->addDeleteFunc('/delete-resource', function () {
            echo 'DELETE';
        });

        $tests = [
            ['GET', '/get-resource'],
            ['POST', '/post-resource'],
            ['PUT', '/put-resource'],
            ['DELETE', '/delete-resource'],
        ];

        foreach ($tests as [$method, $path]) {
            $request = new Request($method, $path);
            $response = $this->router->handle($request);
            $this->assertEquals($method, $response->getContent());
        }
    }

    public function testHandleAllowsMultipleCalls(): void
    {
        $this->routes->addGetFunc('/counter', function () {
            static $counter = 0;
            echo 'Count: ' . ++$counter;
        });

        $request = new Request('GET', '/counter');
        
        $response1 = $this->router->handle($request);
        $this->assertEquals('Count: 1', $response1->getContent());

        $response2 = $this->router->handle($request);
        $this->assertEquals('Count: 2', $response2->getContent());
    }

    public function testHandleWithEmptyResponse(): void
    {
        $this->routes->addGetFunc('/empty', function () {
            // Ничего не выводим
        });

        $request = new Request('GET', '/empty');
        $response = $this->router->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getContent());
    }

    public function testHandleWithNamedRoute(): void
    {
        $this->routes->addGetFunc('/users/{id}', function ($id) {
            echo "User {$id}";
        })->name('users.show');

        $request = new Request('GET', '/users/456');
        $response = $this->router->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('User 456', $response->getContent());
    }
}
