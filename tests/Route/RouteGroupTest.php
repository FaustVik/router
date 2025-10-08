<?php

declare(strict_types=1);

namespace Tests\Route;

use FaustVik\Router\Route\RouteGroup;
use FaustVik\Router\Route\RoutesCollection;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для класса RouteGroup
 *
 * Проверяет:
 * - Группировку маршрутов с префиксом
 * - Применение middleware к группе
 * - Вложенные группы
 * - Различные HTTP методы (get, post, put, delete, patch, any, match)
 * - Анонимные функции в группах
 */
final class RouteGroupTest extends TestCase
{
    private RoutesCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new RoutesCollection();
    }

    public function testPrefixIsAppliedToRoutes(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group->prefix('/api')->get('/users', RouteGroupTestController::class, 'index');

        $this->assertSame('/api/users', $route->getRoute());
    }

    public function testMiddlewareIsAppliedToRoutes(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group
            ->middleware(['AuthMiddleware'])
            ->get('/users', RouteGroupTestController::class, 'index');

        $this->assertSame(['AuthMiddleware'], $route->getMiddleware());
    }

    public function testPrefixAndMiddlewareTogether(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group
            ->prefix('/api')
            ->middleware(['AuthMiddleware', 'LoggingMiddleware'])
            ->get('/users', RouteGroupTestController::class, 'index');

        $this->assertSame('/api/users', $route->getRoute());
        $this->assertSame(['AuthMiddleware', 'LoggingMiddleware'], $route->getMiddleware());
    }

    // ========================================================================
    // HTTP Methods tests
    // ========================================================================

    public function testGetMethod(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group->get('/users', RouteGroupTestController::class, 'index');

        $this->assertSame(['GET'], $route->getMethods());
    }

    public function testPostMethod(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group->post('/users', RouteGroupTestController::class, 'store');

        $this->assertSame(['POST'], $route->getMethods());
    }

    public function testPutMethod(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group->put('/users/{id}', RouteGroupTestController::class, 'update');

        $this->assertSame(['PUT'], $route->getMethods());
    }

    public function testDeleteMethod(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group->delete('/users/{id}', RouteGroupTestController::class, 'destroy');

        $this->assertSame(['DELETE'], $route->getMethods());
    }

    public function testPatchMethod(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group->patch('/users/{id}', RouteGroupTestController::class, 'patch');

        $this->assertSame(['PATCH'], $route->getMethods());
    }

    public function testAnyMethod(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group->any('/users', RouteGroupTestController::class, 'any');

        $this->assertSame(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $route->getMethods());
    }

    public function testMatchMethod(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group->match(['GET', 'POST'], '/users', RouteGroupTestController::class, 'index');

        $this->assertSame(['GET', 'POST'], $route->getMethods());
    }

    // ========================================================================
    // Anonymous functions tests
    // ========================================================================

    public function testGetFunc(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group->getFunc('/test', function () {
            return 'Test';
        });

        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('/test', $route->getRoute());
    }

    public function testPostFunc(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group->postFunc('/test', function () {
            return 'Test';
        });

        $this->assertSame(['POST'], $route->getMethods());
    }

    public function testPutFunc(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group->putFunc('/test/{id}', function ($id) {
            return "Update {$id}";
        });

        $this->assertSame(['PUT'], $route->getMethods());
    }

    public function testDeleteFunc(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group->deleteFunc('/test/{id}', function ($id) {
            return "Delete {$id}";
        });

        $this->assertSame(['DELETE'], $route->getMethods());
    }

    public function testPatchFunc(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group->patchFunc('/test/{id}', function ($id) {
            return "Patch {$id}";
        });

        $this->assertSame(['PATCH'], $route->getMethods());
    }

    public function testAnyFunc(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group->anyFunc('/test', function () {
            return 'Test';
        });

        $this->assertSame(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $route->getMethods());
    }

    public function testMatchFunc(): void
    {
        $group = new RouteGroup($this->collection);
        $route = $group->matchFunc(['GET', 'POST'], '/test', function () {
            return 'Test';
        });

        $this->assertSame(['GET', 'POST'], $route->getMethods());
    }

    // ========================================================================
    // Nested groups tests
    // ========================================================================

    public function testNestedGroups(): void
    {
        $group = new RouteGroup($this->collection);

        $group->prefix('/api')->group(function ($g) {
            $g->prefix('/v1')->group(function ($nested) {
                $nested->get('/users', RouteGroupTestController::class, 'index');
            });
        });

        $routes = $group->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertSame('/api/v1/users', $routes[0]->getRoute());
    }

    public function testNestedGroupsWithMiddleware(): void
    {
        $group = new RouteGroup($this->collection);

        $group
            ->prefix('/api')
            ->middleware(['ApiMiddleware'])
            ->group(function ($g) {
                $g->prefix('/admin')
                    ->middleware(['AuthMiddleware'])
                    ->group(function ($nested) {
                        $nested->get('/users', RouteGroupTestController::class, 'index');
                    });
            });

        $routes = $group->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertSame('/api/admin/users', $routes[0]->getRoute());
        $this->assertSame(['ApiMiddleware', 'AuthMiddleware'], $routes[0]->getMiddleware());
    }

    public function testDeepNestedGroups(): void
    {
        $group = new RouteGroup($this->collection);

        $group->prefix('/api')->group(function ($g1) {
            $g1->prefix('/v1')->group(function ($g2) {
                $g2->prefix('/admin')->group(function ($g3) {
                    $g3->get('/users', RouteGroupTestController::class, 'index');
                });
            });
        });

        $routes = $group->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertSame('/api/v1/admin/users', $routes[0]->getRoute());
    }

    // ========================================================================
    // Multiple routes in group
    // ========================================================================

    public function testMultipleRoutesInGroup(): void
    {
        $group = new RouteGroup($this->collection);

        $group->prefix('/api')->group(function ($g) {
            $g->get('/users', RouteGroupTestController::class, 'index');
            $g->get('/users/{id}', RouteGroupTestController::class, 'show');
            $g->post('/users', RouteGroupTestController::class, 'store');
            $g->put('/users/{id}', RouteGroupTestController::class, 'update');
            $g->delete('/users/{id}', RouteGroupTestController::class, 'destroy');
        });

        $routes = $group->getRoutes();
        $this->assertCount(5, $routes);
        $this->assertSame('/api/users', $routes[0]->getRoute());
        $this->assertSame('/api/users/{id}', $routes[1]->getRoute());
        $this->assertSame('/api/users', $routes[2]->getRoute());
        $this->assertSame('/api/users/{id}', $routes[3]->getRoute());
        $this->assertSame('/api/users/{id}', $routes[4]->getRoute());
    }

    // ========================================================================
    // Routes are added to collection
    // ========================================================================

    public function testRoutesAreAddedToCollection(): void
    {
        $collection = new RoutesCollection();
        $group = new RouteGroup($collection);

        $group->prefix('/api')->group(function ($g) {
            $g->get('/users', RouteGroupTestController::class, 'index');
            $g->post('/users', RouteGroupTestController::class, 'store');
        });

        $routes = $collection->get();
        $this->assertCount(2, $routes);
        $this->assertSame('/api/users', $routes[0]->getRoute());
        $this->assertSame('/api/users', $routes[1]->getRoute());
        $this->assertSame(['GET'], $routes[0]->getMethods());
        $this->assertSame(['POST'], $routes[1]->getMethods());
    }

    // ========================================================================
    // Fluent interface with named routes
    // ========================================================================

    public function testNamedRoutesInGroup(): void
    {
        $group = new RouteGroup($this->collection);

        $route = $group
            ->prefix('/api')
            ->get('/users/{id}', RouteGroupTestController::class, 'show')
            ->name('api.users.show');

        $this->assertSame('api.users.show', $route->getName());
        $this->assertSame('/api/users/{id}', $route->getRoute());
    }

    public function testConstraintsInGroup(): void
    {
        $group = new RouteGroup($this->collection);

        $route = $group
            ->prefix('/api')
            ->get('/users/{id}', RouteGroupTestController::class, 'show')
            ->where('id', '\d+')
            ->name('api.users.show');

        $this->assertSame('api.users.show', $route->getName());
        $this->assertSame('\d+', $route->getConstraints()['id']);
    }

    // ========================================================================
    // Complex scenarios
    // ========================================================================

    public function testComplexGroupScenario(): void
    {
        $collection = new RoutesCollection();
        $group = new RouteGroup($collection);

        $group
            ->prefix('/api/v1')
            ->middleware(['ApiMiddleware', 'VersionMiddleware'])
            ->group(function ($api) {
                // Public routes
                $api->get('/posts', RouteGroupTestController::class, 'index')
                    ->name('api.posts.index');

                // Admin routes
                $api->prefix('/admin')
                    ->middleware(['AuthMiddleware', 'AdminMiddleware'])
                    ->group(function ($admin) {
                        $admin->get('/users', RouteGroupTestController::class, 'index')
                            ->name('api.admin.users.index');

                        $admin->get('/users/{id}', RouteGroupTestController::class, 'show')
                            ->where('id', '\d+')
                            ->name('api.admin.users.show');

                        $admin->post('/users', RouteGroupTestController::class, 'store')
                            ->name('api.admin.users.store');
                    });
            });

        $routes = $collection->get();
        $this->assertCount(4, $routes);

        // Public route
        $this->assertSame('/api/v1/posts', $routes[0]->getRoute());
        $this->assertSame('api.posts.index', $routes[0]->getName());
        $this->assertSame(['ApiMiddleware', 'VersionMiddleware'], $routes[0]->getMiddleware());

        // Admin routes
        $this->assertSame('/api/v1/admin/users', $routes[1]->getRoute());
        $this->assertSame('api.admin.users.index', $routes[1]->getName());
        $this->assertSame(
            ['ApiMiddleware', 'VersionMiddleware', 'AuthMiddleware', 'AdminMiddleware'],
            $routes[1]->getMiddleware()
        );

        $this->assertSame('/api/v1/admin/users/{id}', $routes[2]->getRoute());
        $this->assertSame('api.admin.users.show', $routes[2]->getName());
        $this->assertSame('\d+', $routes[2]->getConstraints()['id']);

        $this->assertSame('/api/v1/admin/users', $routes[3]->getRoute());
        $this->assertSame('api.admin.users.store', $routes[3]->getName());
    }
}

/**
 * Dummy controller для тестов RouteGroupTest
 */
class RouteGroupTestController
{
    public function index(): void
    {
    }

    public function show(): void
    {
    }

    public function store(): void
    {
    }

    public function update(): void
    {
    }

    public function destroy(): void
    {
    }

    public function patch(): void
    {
    }

    public function any(): void
    {
    }
}

