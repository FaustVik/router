<?php

declare(strict_types=1);

namespace Tests\Route;

use FaustVik\Router\Route\Route;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для класса Route
 *
 * Проверяет:
 * - Создание маршрута
 * - Работу с параметрами (route, class, action, methods, alias, middleware)
 * - Named routes функциональность
 * - Constraints для параметров
 * - Fluent interface
 */
final class RouteTest extends TestCase
{
    public function testCreateRoute(): void
    {
        $route = Route::create('/test', RouteTestController::class, 'index', [], ['GET']);

        $this->assertSame('/test', $route->getRoute());
        $this->assertSame(RouteTestController::class, $route->getClass());
        $this->assertSame('index', $route->getAction());
        $this->assertSame(['GET'], $route->getMethods());
    }

    public function testCreateRouteWithParameters(): void
    {
        $route = Route::create('/users/{id}', RouteTestController::class, 'show', [], ['GET', 'POST']);

        $this->assertSame('/users/{id}', $route->getRoute());
        $this->assertSame(RouteTestController::class, $route->getClass());
        $this->assertSame('show', $route->getAction());
        $this->assertSame(['GET', 'POST'], $route->getMethods());
    }

    public function testCreateRouteWithAlias(): void
    {
        $route = Route::create('/test', RouteTestController::class, 'index', [], ['GET'], '/test-alias');

        $this->assertSame('/test-alias', $route->alias());
    }

    public function testSetAlias(): void
    {
        $route = Route::create('/test', RouteTestController::class, 'index', [], ['GET']);
        $route->setAlias('/new-alias');

        $this->assertSame('/new-alias', $route->alias());
    }

    public function testGetArg(): void
    {
        $args = ['param1' => 'value1', 'param2' => 'value2'];
        $route = Route::create('/test', RouteTestController::class, 'index', $args, ['GET']);

        $this->assertSame($args, $route->getArg());
    }

    public function testMiddleware(): void
    {
        $middleware = ['AuthMiddleware', 'LoggingMiddleware'];
        $route = Route::create('/test', RouteTestController::class, 'index', [], ['GET']);
        $route->middleware($middleware);

        $this->assertSame($middleware, $route->getMiddleware());
    }

    public function testFluentInterface(): void
    {
        $route = Route::create('/test', RouteTestController::class, 'index', [], ['GET'])
            ->setAlias('/alias')
            ->middleware(['AuthMiddleware']);

        $this->assertSame('/alias', $route->alias());
        $this->assertSame(['AuthMiddleware'], $route->getMiddleware());
    }

    // ========================================================================
    // Named Routes tests
    // ========================================================================

    public function testNamedRoute(): void
    {
        $route = Route::create('/users/{id}', RouteTestController::class, 'show', [], ['GET'])
            ->name('users.show');

        $this->assertSame('users.show', $route->getName());
    }

    public function testRouteWithoutName(): void
    {
        $route = Route::create('/test', RouteTestController::class, 'index', [], ['GET']);

        $this->assertNull($route->getName());
    }

    public function testFluentInterfaceWithName(): void
    {
        $route = Route::create('/users/{id}', RouteTestController::class, 'show', [], ['GET'])
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
        $route = Route::create('/users/{id}', RouteTestController::class, 'show', [], ['GET'])
            ->where('id', '\d+');

        $constraints = $route->getConstraints();
        $this->assertIsArray($constraints);
        $this->assertArrayHasKey('id', $constraints);
        $this->assertSame('\d+', $constraints['id']);
    }

    public function testMultipleConstraints(): void
    {
        $route = Route::create('/posts/{year}/{slug}', RouteTestController::class, 'show', [], ['GET'])
            ->where('year', '\d{4}')
            ->where('slug', '[a-z\-]+');

        $constraints = $route->getConstraints();
        $this->assertCount(2, $constraints);
        $this->assertSame('\d{4}', $constraints['year']);
        $this->assertSame('[a-z\-]+', $constraints['slug']);
    }

    public function testConstraintsWithoutParameters(): void
    {
        $route = Route::create('/test', RouteTestController::class, 'index', [], ['GET']);

        $constraints = $route->getConstraints();
        $this->assertIsArray($constraints);
        $this->assertEmpty($constraints);
    }

    public function testFluentInterfaceWithConstraints(): void
    {
        $route = Route::create('/users/{id}', RouteTestController::class, 'show', [], ['GET'])
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
        $route = Route::create(
            '/api/v1/users/{userId}/posts/{postId}',
            RouteTestController::class,
            'showUserPost',
            ['apiVersion' => 'v1'],
            ['GET', 'POST']
        )
            ->name('api.users.posts.show')
            ->where('userId', '\d+')
            ->where('postId', '\d+')
            ->middleware(['ApiMiddleware', 'AuthMiddleware', 'RateLimitMiddleware'])
            ->setAlias('/users/{userId}/p/{postId}');

        $this->assertSame('/api/v1/users/{userId}/posts/{postId}', $route->getRoute());
        $this->assertSame('api.users.posts.show', $route->getName());
        $this->assertSame(RouteTestController::class, $route->getClass());
        $this->assertSame('showUserPost', $route->getAction());
        $this->assertSame(['GET', 'POST'], $route->getMethods());
        $this->assertSame('/users/{userId}/p/{postId}', $route->alias());
        $this->assertSame(['apiVersion' => 'v1'], $route->getArg());
        $this->assertSame(
            ['ApiMiddleware', 'AuthMiddleware', 'RateLimitMiddleware'],
            $route->getMiddleware()
        );

        $constraints = $route->getConstraints();
        $this->assertCount(2, $constraints);
        $this->assertSame('\d+', $constraints['userId']);
        $this->assertSame('\d+', $constraints['postId']);
    }
}

/**
 * Dummy controller для тестов RouteTest
 */
class RouteTestController
{
    public function index(): void
    {
    }

    public function show(): void
    {
    }

    public function showUserPost(): void
    {
    }
}

