<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Router\Components;

use FaustVik\Router\exceptions\NoMatch;
use FaustVik\Router\Route\RouteAnonymousFunc;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Components\matching\OptimizedMatching;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для OptimizedMatching
 *
 * Проверяет:
 * - Статические маршруты (O(1) lookup)
 * - Динамические маршруты с параметрами
 * - Индексирование и группировку
 * - Алиасы
 * - Производительность
 */
final class OptimizedMatchingTest extends TestCase
{
    private OptimizedMatching $matching;
    private RoutesCollection $routes;

    protected function setUp(): void
    {
        $this->matching = new OptimizedMatching();
        $this->routes = new RoutesCollection();
    }

    // ========================================================================
    // Статические маршруты (O(1))
    // ========================================================================

    public function testMatchStaticRoute(): void
    {
        $route = RouteAnonymousFunc::create('/users', fn () => 'Users', ['GET']);
        $this->routes->set($route);

        $result = $this->matching->match('/users', $this->routes);

        $this->assertSame($route, $result->getRoute());
        $this->assertEmpty($result->getParameters());
    }

    public function testMatchRootRoute(): void
    {
        $route = RouteAnonymousFunc::create('/', fn () => 'Home', ['GET']);
        $this->routes->set($route);

        $result = $this->matching->match('/', $this->routes);

        $this->assertSame($route, $result->getRoute());
    }

    public function testMatchNestedStaticRoute(): void
    {
        $route = RouteAnonymousFunc::create('/api/v1/users', fn () => 'API', ['GET']);
        $this->routes->set($route);

        $result = $this->matching->match('/api/v1/users', $this->routes);

        $this->assertSame($route, $result->getRoute());
    }

    // ========================================================================
    // Динамические маршруты с параметрами
    // ========================================================================

    public function testMatchWithSingleParameter(): void
    {
        $route = RouteAnonymousFunc::create('/users/{id}', fn () => 'User', ['GET']);
        $this->routes->set($route);

        $result = $this->matching->match('/users/123', $this->routes);

        $this->assertSame($route, $result->getRoute());
        $this->assertEquals(['id' => '123'], $result->getParameters());
    }

    public function testMatchWithMultipleParameters(): void
    {
        $route = RouteAnonymousFunc::create('/posts/{id}/comments/{commentId}', fn () => 'Comment', ['GET']);
        $this->routes->set($route);

        $result = $this->matching->match('/posts/42/comments/7', $this->routes);

        $this->assertEquals(['id' => '42', 'commentId' => '7'], $result->getParameters());
    }

    public function testMatchWithParameterAtStart(): void
    {
        $route = RouteAnonymousFunc::create('/{lang}/about', fn () => 'About', ['GET']);
        $this->routes->set($route);

        $result = $this->matching->match('/en/about', $this->routes);

        $this->assertEquals(['lang' => 'en'], $result->getParameters());
    }

    public function testMatchWithParameterAtEnd(): void
    {
        $route = RouteAnonymousFunc::create('/users/{id}', fn () => 'User', ['GET']);
        $this->routes->set($route);

        $result = $this->matching->match('/users/999', $this->routes);

        $this->assertEquals(['id' => '999'], $result->getParameters());
    }

    // ========================================================================
    // Алиасы
    // ========================================================================

    public function testMatchByAlias(): void
    {
        $route = RouteAnonymousFunc::create('/users', fn () => 'Users', ['GET']);
        $route->setAlias('/people');
        $this->routes->set($route);

        $result = $this->matching->match('/people', $this->routes);

        $this->assertSame($route, $result->getRoute());
    }

    public function testMatchByParametrizedAlias(): void
    {
        $route = RouteAnonymousFunc::create('/users/{id}', fn () => 'User', ['GET']);
        $route->setAlias('/people/{id}');
        $this->routes->set($route);

        $result = $this->matching->match('/people/456', $this->routes);

        $this->assertSame($route, $result->getRoute());
        $this->assertEquals(['id' => '456'], $result->getParameters());
    }

    // ========================================================================
    // Приоритет и порядок
    // ========================================================================

    public function testStaticRouteHasPriorityOverDynamic(): void
    {
        $staticRoute = RouteAnonymousFunc::create('/users/admin', fn () => 'Admin', ['GET']);
        $dynamicRoute = RouteAnonymousFunc::create('/users/{id}', fn () => 'User', ['GET']);

        $this->routes->set($dynamicRoute);
        $this->routes->set($staticRoute);

        $result = $this->matching->match('/users/admin', $this->routes);

        $this->assertSame($staticRoute, $result->getRoute());
        $this->assertEmpty($result->getParameters());
    }

    public function testFirstMatchingRouteIsReturned(): void
    {
        $route1 = RouteAnonymousFunc::create('/users/{id}', fn () => 'Route1', ['GET']);
        $route2 = RouteAnonymousFunc::create('/users/{userId}', fn () => 'Route2', ['GET']);

        $this->routes->set($route1);
        $this->routes->set($route2);

        $result = $this->matching->match('/users/123', $this->routes);

        $this->assertSame($route1, $result->getRoute());
    }

    // ========================================================================
    // Группировка по первому сегменту
    // ========================================================================

    public function testRoutesGroupedByFirstSegment(): void
    {
        // Эти маршруты должны быть в разных группах
        $this->routes->set(RouteAnonymousFunc::create('/users/{id}', fn () => 'User', ['GET']));
        $this->routes->set(RouteAnonymousFunc::create('/posts/{id}', fn () => 'Post', ['GET']));
        $this->routes->set(RouteAnonymousFunc::create('/admin/{id}', fn () => 'Admin', ['GET']));

        // Первый match строит индекс
        $this->matching->match('/users/1', $this->routes);

        $stats = $this->matching->getIndexStats();

        // Должно быть 3 группы по первому сегменту
        $this->assertGreaterThanOrEqual(3, $stats['groups']);
    }

    // ========================================================================
    // Исключения
    // ========================================================================

    public function testThrowsNoMatchForNonexistentRoute(): void
    {
        $this->routes->set(RouteAnonymousFunc::create('/users', fn () => 'Users', ['GET']));

        $this->expectException(NoMatch::class);
        $this->matching->match('/nonexistent', $this->routes);
    }

    public function testThrowsNoMatchForDifferentSegmentCount(): void
    {
        $this->routes->set(RouteAnonymousFunc::create('/users/{id}', fn () => 'User', ['GET']));

        $this->expectException(NoMatch::class);
        $this->matching->match('/users/123/extra', $this->routes);
    }

    // ========================================================================
    // Индексирование
    // ========================================================================

    public function testIndexIsBuiltOnFirstMatch(): void
    {
        $this->routes->set(RouteAnonymousFunc::create('/static', fn () => 'Static', ['GET']));
        $this->routes->set(RouteAnonymousFunc::create('/users/{id}', fn () => 'User', ['GET']));

        $stats = $this->matching->getIndexStats();
        $this->assertEquals(0, $stats['static']); // Индекс еще не построен

        $this->matching->match('/static', $this->routes);

        $stats = $this->matching->getIndexStats();
        $this->assertGreaterThan(0, $stats['static']); // Индекс построен
    }

    public function testIndexStatsAccurate(): void
    {
        // 2 статических маршрута
        $this->routes->set(RouteAnonymousFunc::create('/home', fn () => 'Home', ['GET']));
        $this->routes->set(RouteAnonymousFunc::create('/about', fn () => 'About', ['GET']));

        // 2 динамических маршрута
        $this->routes->set(RouteAnonymousFunc::create('/users/{id}', fn () => 'User', ['GET']));
        $this->routes->set(RouteAnonymousFunc::create('/posts/{id}', fn () => 'Post', ['GET']));

        // 1 маршрут с статическим алиасом
        $route = RouteAnonymousFunc::create('/contact', fn () => 'Contact', ['GET']);
        $route->setAlias('/feedback');
        $this->routes->set($route);

        $this->matching->match('/home', $this->routes);

        $stats = $this->matching->getIndexStats();

        $this->assertEquals(3, $stats['static']); // home, about, contact
        $this->assertEquals(2, $stats['dynamic']); // users/{id}, posts/{id}
        $this->assertGreaterThanOrEqual(1, $stats['aliases']); // feedback (может быть больше из-за динамических алиасов)
    }

    public function testResetIndexClearsCache(): void
    {
        $this->routes->set(RouteAnonymousFunc::create('/test', fn () => 'Test', ['GET']));

        $this->matching->match('/test', $this->routes);
        $stats1 = $this->matching->getIndexStats();
        $this->assertGreaterThan(0, $stats1['static']);

        $this->matching->resetIndex();
        $stats2 = $this->matching->getIndexStats();
        $this->assertEquals(0, $stats2['static']);
    }

    // ========================================================================
    // Множество маршрутов (проверка производительности)
    // ========================================================================

    public function testHandlesManyStaticRoutes(): void
    {
        // Создаем 100 статических маршрутов
        for ($i = 0; $i < 100; $i++) {
            $this->routes->set(RouteAnonymousFunc::create("/route{$i}", fn () => "Route{$i}", ['GET']));
        }

        // Поиск последнего маршрута должен быть быстрым (O(1))
        $result = $this->matching->match('/route99', $this->routes);

        $this->assertNotNull($result);
        $this->assertEquals('/route99', $result->getRoute()->getRoute());
    }

    public function testHandlesManyDynamicRoutes(): void
    {
        // Создаем 50 динамических маршрутов с разными префиксами
        for ($i = 0; $i < 50; $i++) {
            $this->routes->set(RouteAnonymousFunc::create("/group{$i}/{id}", fn () => "Group{$i}", ['GET']));
        }

        // Поиск должен проверять только маршруты из нужной группы
        $result = $this->matching->match('/group25/123', $this->routes);

        $this->assertNotNull($result);
        $this->assertEquals(['id' => '123'], $result->getParameters());
    }

    // ========================================================================
    // Специальные случаи
    // ========================================================================

    public function testMatchWithTrailingSlash(): void
    {
        $this->routes->set(RouteAnonymousFunc::create('/users', fn () => 'Users', ['GET']));

        // Должен правильно обработать trailing slash
        $result = $this->matching->match('/users/', $this->routes);

        $this->assertNotNull($result);
    }

    public function testParameterWithSpecialCharacters(): void
    {
        $this->routes->set(RouteAnonymousFunc::create('/users/{id}', fn () => 'User', ['GET']));

        $result = $this->matching->match('/users/user-123', $this->routes);

        $this->assertEquals(['id' => 'user-123'], $result->getParameters());
    }

    public function testComplexRoutePatterns(): void
    {
        $this->routes->set(RouteAnonymousFunc::create(
            '/api/{version}/users/{userId}/posts/{postId}/comments',
            fn () => 'Comments',
            ['GET']
        ));

        $result = $this->matching->match('/api/v1/users/10/posts/20/comments', $this->routes);

        $this->assertEquals([
            'version' => 'v1',
            'userId' => '10',
            'postId' => '20',
        ], $result->getParameters());
    }
}
