<?php

declare(strict_types=1);

namespace Tests\Route;

use FaustVik\Router\Route\RouteAnonymousFunc;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для класса RouteAnonymousFunc
 *
 * Проверяет:
 * - Создание маршрута с анонимной функцией
 * - Работу с параметрами
 * - Named routes функциональность
 * - Constraints для параметров
 * - Fluent interface
 */
final class RouteAnonymousFuncTest extends TestCase
{
    public function testCreateRoute(): void
    {
        $func = function () {
            return 'Hello';
        };

        $route = RouteAnonymousFunc::create('/test', $func, ['GET']);

        $this->assertSame('/test', $route->getRoute());
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertInstanceOf(\Closure::class, $route->getFunc());
    }

    public function testCreateRouteWithParameters(): void
    {
        $func = function ($id) {
            return "User ID: {$id}";
        };

        $route = RouteAnonymousFunc::create('/users/{id}', $func, ['GET', 'POST']);

        $this->assertSame('/users/{id}', $route->getRoute());
        $this->assertSame(['GET', 'POST'], $route->getMethods());
        $this->assertInstanceOf(\Closure::class, $route->getFunc());
    }

    public function testCreateRouteWithAlias(): void
    {
        $func = function () {
            return 'Test';
        };

        $route = RouteAnonymousFunc::create('/test', $func, ['GET'], '/test-alias');

        $this->assertSame('/test-alias', $route->alias());
    }

    public function testSetAlias(): void
    {
        $func = function () {
            return 'Test';
        };

        $route = RouteAnonymousFunc::create('/test', $func, ['GET']);
        $route->setAlias('/new-alias');

        $this->assertSame('/new-alias', $route->alias());
    }

    public function testMiddleware(): void
    {
        $func = function () {
            return 'Test';
        };

        $middleware = ['AuthMiddleware', 'LoggingMiddleware'];
        $route = RouteAnonymousFunc::create('/test', $func, ['GET']);
        $route->middleware($middleware);

        $this->assertSame($middleware, $route->getMiddleware());
    }

    public function testFluentInterface(): void
    {
        $func = function () {
            return 'Test';
        };

        $route = RouteAnonymousFunc::create('/test', $func, ['GET'])
            ->setAlias('/alias')
            ->middleware(['AuthMiddleware']);

        $this->assertSame('/alias', $route->alias());
        $this->assertSame(['AuthMiddleware'], $route->getMiddleware());
    }

    public function testFunctionExecution(): void
    {
        $func = function ($name) {
            return "Hello, {$name}!";
        };

        $route = RouteAnonymousFunc::create('/hello/{name}', $func, ['GET']);
        $closure = $route->getFunc();

        $result = $closure('World');
        $this->assertSame('Hello, World!', $result);
    }

    // ========================================================================
    // Named Routes tests
    // ========================================================================

    public function testNamedRoute(): void
    {
        $func = function ($id) {
            return "User {$id}";
        };

        $route = RouteAnonymousFunc::create('/users/{id}', $func, ['GET'])
            ->name('users.show');

        $this->assertSame('users.show', $route->getName());
    }

    public function testRouteWithoutName(): void
    {
        $func = function () {
            return 'Test';
        };

        $route = RouteAnonymousFunc::create('/test', $func, ['GET']);

        $this->assertNull($route->getName());
    }

    public function testFluentInterfaceWithName(): void
    {
        $func = function ($id) {
            return "User {$id}";
        };

        $route = RouteAnonymousFunc::create('/users/{id}', $func, ['GET'])
            ->name('users.show')
            ->middleware(['AuthMiddleware'])
            ->setAlias('/user/{id}');

        $this->assertSame('users.show', $route->getName());
        $this->assertSame(['AuthMiddleware'], $route->getMiddleware());
        $this->assertSame('/user/{id}', $route->alias());
    }

    // ========================================================================
    // Constraints tests
    // ========================================================================

    public function testConstraints(): void
    {
        $func = function ($id) {
            return "User {$id}";
        };

        $route = RouteAnonymousFunc::create('/users/{id}', $func, ['GET'])
            ->where('id', '\d+');

        $constraints = $route->getConstraints();
        $this->assertIsArray($constraints);
        $this->assertArrayHasKey('id', $constraints);
        $this->assertSame('\d+', $constraints['id']);
    }

    public function testMultipleConstraints(): void
    {
        $func = function ($year, $slug) {
            return "Post {$year}/{$slug}";
        };

        $route = RouteAnonymousFunc::create('/posts/{year}/{slug}', $func, ['GET'])
            ->where('year', '\d{4}')
            ->where('slug', '[a-z\-]+');

        $constraints = $route->getConstraints();
        $this->assertCount(2, $constraints);
        $this->assertSame('\d{4}', $constraints['year']);
        $this->assertSame('[a-z\-]+', $constraints['slug']);
    }

    public function testConstraintsWithoutParameters(): void
    {
        $func = function () {
            return 'Test';
        };

        $route = RouteAnonymousFunc::create('/test', $func, ['GET']);

        $constraints = $route->getConstraints();
        $this->assertIsArray($constraints);
        $this->assertEmpty($constraints);
    }

    public function testFluentInterfaceWithConstraints(): void
    {
        $func = function ($id) {
            return "User {$id}";
        };

        $route = RouteAnonymousFunc::create('/users/{id}', $func, ['GET'])
            ->name('users.show')
            ->where('id', '\d+')
            ->middleware(['AuthMiddleware']);

        $this->assertSame('users.show', $route->getName());
        $this->assertSame('\d+', $route->getConstraints()['id']);
        $this->assertSame(['AuthMiddleware'], $route->getMiddleware());
    }

    // ========================================================================
    // Complex scenarios
    // ========================================================================

    public function testComplexRoute(): void
    {
        $func = function ($userId, $postId) {
            return "User {$userId}, Post {$postId}";
        };

        $route = RouteAnonymousFunc::create(
            '/api/v1/users/{userId}/posts/{postId}',
            $func,
            ['GET', 'POST'],
            '/users/{userId}/p/{postId}'
        )
            ->name('api.users.posts.show')
            ->where('userId', '\d+')
            ->where('postId', '\d+')
            ->middleware(['ApiMiddleware', 'AuthMiddleware', 'RateLimitMiddleware']);

        $this->assertSame('/api/v1/users/{userId}/posts/{postId}', $route->getRoute());
        $this->assertSame('api.users.posts.show', $route->getName());
        $this->assertSame(['GET', 'POST'], $route->getMethods());
        $this->assertSame('/users/{userId}/p/{postId}', $route->alias());
        $this->assertSame(
            ['ApiMiddleware', 'AuthMiddleware', 'RateLimitMiddleware'],
            $route->getMiddleware()
        );

        $constraints = $route->getConstraints();
        $this->assertCount(2, $constraints);
        $this->assertSame('\d+', $constraints['userId']);
        $this->assertSame('\d+', $constraints['postId']);

        // Проверяем выполнение функции
        $closure = $route->getFunc();
        $result = $closure(123, 456);
        $this->assertSame('User 123, Post 456', $result);
    }

    public function testClosureWithTypeHints(): void
    {
        $func = function (int $id, string $name): string {
            return "ID: {$id}, Name: {$name}";
        };

        $route = RouteAnonymousFunc::create('/test/{id}/{name}', $func, ['GET']);
        $closure = $route->getFunc();

        $result = $closure(123, 'John');
        $this->assertSame('ID: 123, Name: John', $result);
    }

    public function testClosureWithDefaultValues(): void
    {
        $func = function ($id, $type = 'default') {
            return "ID: {$id}, Type: {$type}";
        };

        $route = RouteAnonymousFunc::create('/items/{id}/{type?}', $func, ['GET']);
        $closure = $route->getFunc();

        $result1 = $closure(123);
        $this->assertSame('ID: 123, Type: default', $result1);

        $result2 = $closure(456, 'premium');
        $this->assertSame('ID: 456, Type: premium', $result2);
    }
}

