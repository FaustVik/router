<?php

declare(strict_types=1);

namespace Tests\Route;

use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RouteGroup;
use FaustVik\Router\Route\RoutesCollection;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для класса RoutesCollection
 *
 * Проверяет:
 * - Добавление маршрутов
 * - Helper методы для различных HTTP методов
 * - Группировку маршрутов
 * - Работу с анонимными функциями
 */
final class RoutesCollectionTest extends TestCase
{
    private RoutesCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new RoutesCollection();
    }

    // ========================================================================
    // Basic functionality tests
    // ========================================================================

    public function testSetAndGetRoutes(): void
    {
        $route1 = Route::create('/test1', RoutesCollectionTestController::class, 'index', [], ['GET']);
        $route2 = Route::create('/test2', RoutesCollectionTestController::class, 'show', [], ['POST']);

        $this->collection->set($route1, $route2);

        $routes = $this->collection->get();
        $this->assertCount(2, $routes);
        $this->assertSame($route1, $routes[0]);
        $this->assertSame($route2, $routes[1]);
    }

    public function testMultipleSetCalls(): void
    {
        $route1 = Route::create('/test1', RoutesCollectionTestController::class, 'index', [], ['GET']);
        $route2 = Route::create('/test2', RoutesCollectionTestController::class, 'show', [], ['POST']);
        $route3 = Route::create('/test3', RoutesCollectionTestController::class, 'store', [], ['PUT']);

        $this->collection->set($route1);
        $this->collection->set($route2, $route3);

        $routes = $this->collection->get();
        $this->assertCount(3, $routes);
    }

    // ========================================================================
    // Helper methods for HTTP methods
    // ========================================================================

    public function testAddGet(): void
    {
        $route = $this->collection->addGet('/users', RoutesCollectionTestController::class, 'index');

        $this->assertSame('/users', $route->getRoute());
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame(RoutesCollectionTestController::class, $route->getClass());
        $this->assertSame('index', $route->getAction());
    }

    public function testAddPost(): void
    {
        $route = $this->collection->addPost('/users', RoutesCollectionTestController::class, 'store');

        $this->assertSame('/users', $route->getRoute());
        $this->assertSame(['POST'], $route->getMethods());
    }

    public function testAddPut(): void
    {
        $route = $this->collection->addPut('/users/{id}', RoutesCollectionTestController::class, 'update');

        $this->assertSame('/users/{id}', $route->getRoute());
        $this->assertSame(['PUT'], $route->getMethods());
    }

    public function testAddDelete(): void
    {
        $route = $this->collection->addDelete('/users/{id}', RoutesCollectionTestController::class, 'destroy');

        $this->assertSame('/users/{id}', $route->getRoute());
        $this->assertSame(['DELETE'], $route->getMethods());
    }

    public function testAddPatch(): void
    {
        $route = $this->collection->addPatch('/users/{id}', RoutesCollectionTestController::class, 'patch');

        $this->assertSame('/users/{id}', $route->getRoute());
        $this->assertSame(['PATCH'], $route->getMethods());
    }

    public function testAddAny(): void
    {
        $route = $this->collection->addAny('/users', RoutesCollectionTestController::class, 'any');

        $this->assertSame('/users', $route->getRoute());
        $this->assertSame(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $route->getMethods());
    }

    public function testAddMatch(): void
    {
        $route = $this->collection->addMatch(
            ['GET', 'POST'],
            '/users',
            RoutesCollectionTestController::class,
            'index'
        );

        $this->assertSame('/users', $route->getRoute());
        $this->assertSame(['GET', 'POST'], $route->getMethods());
    }

    // ========================================================================
    // Helper methods return route objects for chaining
    // ========================================================================

    public function testHelperMethodsReturnRouteForChaining(): void
    {
        $route = $this->collection
            ->addGet('/users/{id}', RoutesCollectionTestController::class, 'show')
            ->name('users.show')
            ->where('id', '\d+')
            ->middleware(['AuthMiddleware']);

        $this->assertSame('users.show', $route->getName());
        $this->assertSame('\d+', $route->getConstraints()['id']);
        $this->assertSame(['AuthMiddleware'], $route->getMiddleware());
    }

    // ========================================================================
    // Anonymous functions tests
    // ========================================================================

    public function testAddGetFunc(): void
    {
        $func = function () {
            return 'Test';
        };

        $route = $this->collection->addGetFunc('/test', $func);

        $this->assertSame('/test', $route->getRoute());
        $this->assertSame(['GET'], $route->getMethods());
    }

    public function testAddPostFunc(): void
    {
        $func = function () {
            return 'Test';
        };

        $route = $this->collection->addPostFunc('/test', $func);

        $this->assertSame('/test', $route->getRoute());
        $this->assertSame(['POST'], $route->getMethods());
    }

    public function testAddPutFunc(): void
    {
        $func = function ($id) {
            return "Update {$id}";
        };

        $route = $this->collection->addPutFunc('/test/{id}', $func);

        $this->assertSame('/test/{id}', $route->getRoute());
        $this->assertSame(['PUT'], $route->getMethods());
    }

    public function testAddDeleteFunc(): void
    {
        $func = function ($id) {
            return "Delete {$id}";
        };

        $route = $this->collection->addDeleteFunc('/test/{id}', $func);

        $this->assertSame('/test/{id}', $route->getRoute());
        $this->assertSame(['DELETE'], $route->getMethods());
    }

    public function testAddPatchFunc(): void
    {
        $func = function ($id) {
            return "Patch {$id}";
        };

        $route = $this->collection->addPatchFunc('/test/{id}', $func);

        $this->assertSame('/test/{id}', $route->getRoute());
        $this->assertSame(['PATCH'], $route->getMethods());
    }

    public function testAddAnyFunc(): void
    {
        $func = function () {
            return 'Test';
        };

        $route = $this->collection->addAnyFunc('/test', $func);

        $this->assertSame('/test', $route->getRoute());
        $this->assertSame(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $route->getMethods());
    }

    public function testAddMatchFunc(): void
    {
        $func = function () {
            return 'Test';
        };

        $route = $this->collection->addMatchFunc(['GET', 'POST'], '/test', $func);

        $this->assertSame('/test', $route->getRoute());
        $this->assertSame(['GET', 'POST'], $route->getMethods());
    }

    // ========================================================================
    // Grouping tests
    // ========================================================================

    public function testPrefixCreatesGroup(): void
    {
        $group = $this->collection->prefix('/api');

        $this->assertInstanceOf(RouteGroup::class, $group);
    }

    public function testMiddlewareCreatesGroup(): void
    {
        $group = $this->collection->middleware(['AuthMiddleware']);

        $this->assertInstanceOf(RouteGroup::class, $group);
    }

    public function testGroupMethod(): void
    {
        $this->collection->group(function ($group): void {
            $group->prefix('/api')->group(function ($api): void {
                $api->get('/users', RoutesCollectionTestController::class, 'index');
            });
        });

        $routes = $this->collection->get();
        $this->assertCount(1, $routes);
        $this->assertSame('/api/users', $routes[0]->getRoute());
    }

    public function testPrefixChaining(): void
    {
        $this->collection->prefix('/api')->group(function ($group): void {
            $group->get('/users', RoutesCollectionTestController::class, 'index');
            $group->get('/posts', RoutesCollectionTestController::class, 'posts');
        });

        $routes = $this->collection->get();
        $this->assertCount(2, $routes);
        $this->assertSame('/api/users', $routes[0]->getRoute());
        $this->assertSame('/api/posts', $routes[1]->getRoute());
    }

    // ========================================================================
    // Complex scenarios
    // ========================================================================

    public function testComplexRoutesScenario(): void
    {
        // Simple routes
        $this->collection->addGet('/', RoutesCollectionTestController::class, 'home')
            ->name('home');

        // API routes with prefix and middleware
        $this->collection
            ->prefix('/api')
            ->middleware(['ApiMiddleware'])
            ->group(function ($api): void {
                $api->get('/posts', RoutesCollectionTestController::class, 'posts')
                    ->name('api.posts.index');

                $api->get('/posts/{id}', RoutesCollectionTestController::class, 'showPost')
                    ->where('id', '\d+')
                    ->name('api.posts.show');

                // Admin routes
                $api->prefix('/admin')
                    ->middleware(['AuthMiddleware'])
                    ->group(function ($admin): void {
                        $admin->get('/users', RoutesCollectionTestController::class, 'users')
                            ->name('api.admin.users');

                        $admin->post('/users', RoutesCollectionTestController::class, 'storeUser')
                            ->name('api.admin.users.store');
                    });
            });

        // Anonymous function route
        $this->collection->addGetFunc('/hello/{name}', function ($name) {
            return "Hello, {$name}!";
        })->name('hello');

        $routes = $this->collection->get();
        $this->assertCount(6, $routes);

        // Check home route
        $this->assertSame('/', $routes[0]->getRoute());
        $this->assertSame('home', $routes[0]->getName());

        // Check API posts routes
        $this->assertSame('/api/posts', $routes[1]->getRoute());
        $this->assertSame('api.posts.index', $routes[1]->getName());

        $this->assertSame('/api/posts/{id}', $routes[2]->getRoute());
        $this->assertSame('api.posts.show', $routes[2]->getName());
        $this->assertSame('\d+', $routes[2]->getConstraints()['id']);

        // Check admin routes
        $this->assertSame('/api/admin/users', $routes[3]->getRoute());
        $this->assertSame('api.admin.users', $routes[3]->getName());
        $this->assertSame(['ApiMiddleware', 'AuthMiddleware'], $routes[3]->getMiddleware());

        $this->assertSame('/api/admin/users', $routes[4]->getRoute());
        $this->assertSame('api.admin.users.store', $routes[4]->getName());
        $this->assertSame(['POST'], $routes[4]->getMethods());

        // Check anonymous function route
        $this->assertSame('/hello/{name}', $routes[5]->getRoute());
        $this->assertSame('hello', $routes[5]->getName());
    }

    public function testRESTfulResourceRoutes(): void
    {
        $this->collection->prefix('/users')->group(function ($group): void {
            $group->get('', RoutesCollectionTestController::class, 'index')->name('users.index');
            $group->get('/{id}', RoutesCollectionTestController::class, 'show')->name('users.show')
                ->where('id', '\d+');
            $group->post('', RoutesCollectionTestController::class, 'store')->name('users.store');
            $group->put('/{id}', RoutesCollectionTestController::class, 'update')->name('users.update')
                ->where('id', '\d+');
            $group->delete('/{id}', RoutesCollectionTestController::class, 'destroy')->name('users.destroy')
                ->where('id', '\d+');
        });

        $routes = $this->collection->get();
        $this->assertCount(5, $routes);

        // Index
        $this->assertSame('/users', $routes[0]->getRoute());
        $this->assertSame(['GET'], $routes[0]->getMethods());

        // Show
        $this->assertSame('/users/{id}', $routes[1]->getRoute());
        $this->assertSame(['GET'], $routes[1]->getMethods());

        // Store
        $this->assertSame('/users', $routes[2]->getRoute());
        $this->assertSame(['POST'], $routes[2]->getMethods());

        // Update
        $this->assertSame('/users/{id}', $routes[3]->getRoute());
        $this->assertSame(['PUT'], $routes[3]->getMethods());

        // Destroy
        $this->assertSame('/users/{id}', $routes[4]->getRoute());
        $this->assertSame(['DELETE'], $routes[4]->getMethods());
    }
}

/**
 * Dummy controller для тестов RoutesCollectionTest
 */
class RoutesCollectionTestController
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

    public function home(): void
    {
    }

    public function posts(): void
    {
    }

    public function showPost(): void
    {
    }

    public function users(): void
    {
    }

    public function storeUser(): void
    {
    }
}
