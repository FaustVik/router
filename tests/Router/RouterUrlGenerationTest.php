<?php

declare(strict_types=1);

namespace Tests\Router;

use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RouteAnonymousFunc;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для URL Generation в Router
 *
 * Проверяет:
 * - Генерацию URL по имени маршрута
 * - Подстановку параметров
 * - Опциональные параметры
 * - Constraints валидацию
 * - Обработку ошибок
 */
final class RouterUrlGenerationTest extends TestCase
{
    private Router $router;
    private RoutesCollection $collection;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->collection = new RoutesCollection();
    }

    // ========================================================================
    // Basic URL generation tests
    // ========================================================================

    public function testUrlGenerationSimpleRoute(): void
    {
        $route = Route::create('/users', RouterUrlGenerationTestController::class, 'index', [], ['GET'])
            ->name('users.index');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        $url = $this->router->url('users.index');
        $this->assertSame('/users', $url);
    }

    public function testUrlGenerationWithParameter(): void
    {
        $route = Route::create('/users/{id}', RouterUrlGenerationTestController::class, 'show', [], ['GET'])
            ->name('users.show');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        $url = $this->router->url('users.show', ['id' => 123]);
        $this->assertSame('/users/123', $url);
    }

    public function testUrlGenerationWithMultipleParameters(): void
    {
        $route = Route::create('/posts/{year}/{month}/{slug}', RouterUrlGenerationTestController::class, 'show', [], ['GET'])
            ->name('posts.show');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        $url = $this->router->url('posts.show', [
            'year' => 2025,
            'month' => 10,
            'slug' => 'my-post',
        ]);
        $this->assertSame('/posts/2025/10/my-post', $url);
    }

    public function testUrlGenerationWithSpecialCharacters(): void
    {
        $route = Route::create('/search/{query}', RouterUrlGenerationTestController::class, 'search', [], ['GET'])
            ->name('search');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        $url = $this->router->url('search', ['query' => 'hello world']);
        $this->assertSame('/search/hello%20world', $url);
    }

    // ========================================================================
    // Optional parameters tests
    // ========================================================================

    public function testUrlGenerationWithOptionalParameterProvided(): void
    {
        $route = Route::create('/posts/{id?}', RouterUrlGenerationTestController::class, 'index', [], ['GET'])
            ->name('posts.index');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        $url = $this->router->url('posts.index', ['id' => 123]);
        $this->assertSame('/posts/123', $url);
    }

    public function testUrlGenerationWithOptionalParameterOmitted(): void
    {
        $route = Route::create('/posts/{id?}', RouterUrlGenerationTestController::class, 'index', [], ['GET'])
            ->name('posts.index');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        $url = $this->router->url('posts.index');
        $this->assertSame('/posts', $url);
    }

    public function testUrlGenerationWithMultipleOptionalParameters(): void
    {
        $route = Route::create('/posts/{year?}/{month?}', RouterUrlGenerationTestController::class, 'index', [], ['GET'])
            ->name('posts.archive');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        // No parameters
        $url1 = $this->router->url('posts.archive');
        $this->assertSame('/posts', $url1);

        // Only year
        $url2 = $this->router->url('posts.archive', ['year' => 2025]);
        $this->assertSame('/posts/2025', $url2);

        // Year and month
        $url3 = $this->router->url('posts.archive', ['year' => 2025, 'month' => 10]);
        $this->assertSame('/posts/2025/10', $url3);
    }

    // ========================================================================
    // Constraints tests
    // ========================================================================

    public function testUrlGenerationValidatesConstraints(): void
    {
        $route = Route::create('/users/{id}', RouterUrlGenerationTestController::class, 'show', [], ['GET'])
            ->name('users.show')
            ->where('id', '\d+');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        // Valid value
        $url = $this->router->url('users.show', ['id' => 123]);
        $this->assertSame('/users/123', $url);
    }

    public function testUrlGenerationFailsOnInvalidConstraint(): void
    {
        $route = Route::create('/users/{id}', RouterUrlGenerationTestController::class, 'show', [], ['GET'])
            ->name('users.show')
            ->where('id', '\d+');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Parameter 'id' with value 'abc' does not match constraint pattern");

        $this->router->url('users.show', ['id' => 'abc']);
    }

    public function testUrlGenerationWithMultipleConstraints(): void
    {
        $route = Route::create('/posts/{year}/{slug}', RouterUrlGenerationTestController::class, 'show', [], ['GET'])
            ->name('posts.show')
            ->where('year', '\d{4}')
            ->where('slug', '[a-z\-]+');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        $url = $this->router->url('posts.show', ['year' => 2025, 'slug' => 'my-post']);
        $this->assertSame('/posts/2025/my-post', $url);
    }

    // ========================================================================
    // Error handling tests
    // ========================================================================

    public function testUrlGenerationThrowsExceptionForNonExistentRoute(): void
    {
        $this->router->setCollection($this->collection);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Route 'non.existent' not found");

        $this->router->url('non.existent');
    }

    public function testUrlGenerationThrowsExceptionForMissingRequiredParameter(): void
    {
        $route = Route::create('/users/{id}', RouterUrlGenerationTestController::class, 'show', [], ['GET'])
            ->name('users.show');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required parameter 'id'");

        $this->router->url('users.show');
    }

    public function testUrlGenerationThrowsExceptionForMissingRequiredParameterWithOptionals(): void
    {
        $route = Route::create('/posts/{id}/{slug?}', RouterUrlGenerationTestController::class, 'show', [], ['GET'])
            ->name('posts.show');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required parameter 'id'");

        $this->router->url('posts.show', ['slug' => 'my-post']);
    }

    // ========================================================================
    // Helper methods tests
    // ========================================================================

    public function testHasMethod(): void
    {
        $route = Route::create('/users', RouterUrlGenerationTestController::class, 'index', [], ['GET'])
            ->name('users.index');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        $this->assertTrue($this->router->has('users.index'));
        $this->assertFalse($this->router->has('non.existent'));
    }

    public function testGetNamedRoutes(): void
    {
        $route1 = Route::create('/users', RouterUrlGenerationTestController::class, 'index', [], ['GET'])
            ->name('users.index');
        $route2 = Route::create('/posts', RouterUrlGenerationTestController::class, 'index', [], ['GET'])
            ->name('posts.index');
        $route3 = Route::create('/about', RouterUrlGenerationTestController::class, 'about', [], ['GET']); // No name

        $this->collection->set($route1, $route2, $route3);
        $this->router->setCollection($this->collection);

        $namedRoutes = $this->router->getNamedRoutes();
        $this->assertCount(2, $namedRoutes);
        $this->assertArrayHasKey('users.index', $namedRoutes);
        $this->assertArrayHasKey('posts.index', $namedRoutes);
        $this->assertArrayNotHasKey('about', $namedRoutes);
    }

    public function testGetRouteByName(): void
    {
        $route = Route::create('/users', RouterUrlGenerationTestController::class, 'index', [], ['GET'])
            ->name('users.index');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        $foundRoute = $this->router->getRouteByName('users.index');
        $this->assertSame($route, $foundRoute);

        $notFoundRoute = $this->router->getRouteByName('non.existent');
        $this->assertNull($notFoundRoute);
    }

    // ========================================================================
    // Complex scenarios
    // ========================================================================

    public function testComplexUrlGenerationScenario(): void
    {
        // Setup routes
        $this->collection->addGet('/', RouterUrlGenerationTestController::class, 'home')
            ->name('home');

        $this->collection->prefix('/api')->group(function ($api): void {
            $api->get('/posts', RouterUrlGenerationTestController::class, 'posts')
                ->name('api.posts.index');

            $api->get('/posts/{id}', RouterUrlGenerationTestController::class, 'showPost')
                ->where('id', '\d+')
                ->name('api.posts.show');

            $api->prefix('/users')->group(function ($users): void {
                $users->get('', RouterUrlGenerationTestController::class, 'users')
                    ->name('api.users.index');

                $users->get('/{id}', RouterUrlGenerationTestController::class, 'showUser')
                    ->where('id', '\d+')
                    ->name('api.users.show');

                $users->get('/{id}/posts/{postId?}', RouterUrlGenerationTestController::class, 'userPosts')
                    ->where('id', '\d+')
                    ->where('postId', '\d+')
                    ->name('api.users.posts');
            });
        });

        $this->router->setCollection($this->collection);

        // Test URLs
        $this->assertSame('/', $this->router->url('home'));
        $this->assertSame('/api/posts', $this->router->url('api.posts.index'));
        $this->assertSame('/api/posts/123', $this->router->url('api.posts.show', ['id' => 123]));
        $this->assertSame('/api/users', $this->router->url('api.users.index'));
        $this->assertSame('/api/users/456', $this->router->url('api.users.show', ['id' => 456]));
        $this->assertSame(
            '/api/users/456/posts',
            $this->router->url('api.users.posts', ['id' => 456])
        );
        $this->assertSame(
            '/api/users/456/posts/789',
            $this->router->url('api.users.posts', ['id' => 456, 'postId' => 789])
        );
    }

    public function testUrlGenerationWithAnonymousFunctions(): void
    {
        $func = function ($name) {
            return "Hello, {$name}!";
        };

        $route = RouteAnonymousFunc::create('/hello/{name}', $func, ['GET'])
            ->name('hello');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        $url = $this->router->url('hello', ['name' => 'World']);
        $this->assertSame('/hello/World', $url);
    }

    public function testRootPathGeneration(): void
    {
        $route = Route::create('/', RouterUrlGenerationTestController::class, 'index', [], ['GET'])
            ->name('home');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        $url = $this->router->url('home');
        $this->assertSame('/', $url);
    }

    public function testUrlGenerationCleansDoubleSlashes(): void
    {
        // Маршрут с потенциальным двойным слешем
        $route = Route::create('//users//profile//', RouterUrlGenerationTestController::class, 'profile', [], ['GET'])
            ->name('profile');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        $url = $this->router->url('profile');
        // URL должен быть очищен от двойных слешей
        $this->assertStringNotContainsString('//', $url);
    }

    public function testUrlGenerationWithConstraintsInOptionalParams(): void
    {
        $route = Route::create('/archive/{year?}/{month?}', RouterUrlGenerationTestController::class, 'archive', [], ['GET'])
            ->name('archive')
            ->where('year', '\d{4}')
            ->where('month', '\d{1,2}');

        $this->collection->set($route);
        $this->router->setCollection($this->collection);

        // Valid values
        $url1 = $this->router->url('archive', ['year' => 2025]);
        $this->assertSame('/archive/2025', $url1);

        $url2 = $this->router->url('archive', ['year' => 2025, 'month' => 10]);
        $this->assertSame('/archive/2025/10', $url2);

        // Invalid value should throw exception
        $this->expectException(InvalidArgumentException::class);
        $this->router->url('archive', ['year' => 'abc']);
    }
}

/**
 * Dummy controller для тестов RouterUrlGenerationTest
 */
class RouterUrlGenerationTestController
{
    public function index(): void
    {
    }

    public function show(): void
    {
    }

    public function search(): void
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

    public function showUser(): void
    {
    }

    public function userPosts(): void
    {
    }

    public function about(): void
    {
    }

    public function profile(): void
    {
    }

    public function archive(): void
    {
    }
}
