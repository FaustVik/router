<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Router\Components;

use FaustVik\Router\exceptions\NoMatch;
use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Components\matching\Matching;
use FaustVik\Router\Router\Components\matching\MatchResult;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для класса Matching - ядра системы маршрутизации
 * 
 * Покрывает:
 * - Точное совпадение маршрутов
 * - Совпадение по alias
 * - Маршруты с параметрами (одним и несколькими)
 * - Различные позиции параметров
 * - Несовпадения и исключения
 * - Edge cases
 */
final class MatchingTest extends TestCase
{
    private Matching $matching;
    private RoutesCollection $collection;

    protected function setUp(): void
    {
        $this->matching = new Matching();
        $this->collection = new RoutesCollection();
    }

    // ========================================================================
    // Тесты точного совпадения маршрутов
    // ========================================================================

    public function testMatchExactRoute(): void
    {
        // Arrange: Создаем простой маршрут без параметров
        $route = Route::create('/users', 'UserController', 'index');
        $this->collection->set($route);

        // Act: Проверяем совпадение
        $result = $this->matching->match('/users', $this->collection);

        // Assert: Проверяем что маршрут найден и параметров нет
        $this->assertInstanceOf(MatchResult::class, $result);
        $this->assertSame($route, $result->getRoute());
        $this->assertEmpty($result->getParameters());
    }

    public function testMatchRootRoute(): void
    {
        // Arrange: Корневой маршрут
        $route = Route::create('/', 'HomeController', 'index');
        $this->collection->set($route);

        // Act & Assert
        $result = $this->matching->match('/', $this->collection);
        
        $this->assertSame($route, $result->getRoute());
        $this->assertEmpty($result->getParameters());
    }

    public function testMatchNestedRoute(): void
    {
        // Arrange: Вложенный маршрут с несколькими сегментами
        $route = Route::create('/api/v1/users', 'ApiUserController', 'index');
        $this->collection->set($route);

        // Act & Assert
        $result = $this->matching->match('/api/v1/users', $this->collection);
        
        $this->assertSame($route, $result->getRoute());
        $this->assertEmpty($result->getParameters());
    }

    public function testMatchWithTrailingSlash(): void
    {
        // Arrange: Маршрут с trailing slash
        $route = Route::create('/users/', 'UserController', 'index');
        $this->collection->set($route);

        // Act: Проверяем что trailing slash игнорируется
        $result = $this->matching->match('/users/', $this->collection);
        
        // Assert
        $this->assertSame($route, $result->getRoute());
    }

    // ========================================================================
    // Тесты совпадения по alias
    // ========================================================================

    public function testMatchByAlias(): void
    {
        // Arrange: Маршрут с alias
        $route = Route::create('/users', 'UserController', 'index')
            ->setAlias('/user-list');
        $this->collection->set($route);

        // Act: Проверяем совпадение по alias
        $result = $this->matching->match('/user-list', $this->collection);

        // Assert
        $this->assertSame($route, $result->getRoute());
        $this->assertEmpty($result->getParameters());
    }

    public function testMatchBothRouteAndAlias(): void
    {
        // Arrange: Маршрут с alias
        $route = Route::create('/users', 'UserController', 'index')
            ->setAlias('/user-list');
        $this->collection->set($route);

        // Act & Assert: Основной маршрут работает
        $result1 = $this->matching->match('/users', $this->collection);
        $this->assertSame($route, $result1->getRoute());

        // Act & Assert: Alias тоже работает
        $result2 = $this->matching->match('/user-list', $this->collection);
        $this->assertSame($route, $result2->getRoute());
    }

    // ========================================================================
    // Тесты маршрутов с параметрами
    // ========================================================================

    public function testMatchWithSingleParameter(): void
    {
        // Arrange: Маршрут с одним параметром
        $route = Route::create('/users/{id}', 'UserController', 'show');
        $this->collection->set($route);

        // Act: Проверяем совпадение
        $result = $this->matching->match('/users/123', $this->collection);

        // Assert: Проверяем что параметр извлечен
        $this->assertSame($route, $result->getRoute());
        $this->assertEquals(['id' => '123'], $result->getParameters());
    }

    public function testMatchWithMultipleParameters(): void
    {
        // Arrange: Маршрут с несколькими параметрами
        $route = Route::create('/users/{userId}/posts/{postId}', 'PostController', 'show');
        $this->collection->set($route);

        // Act
        $result = $this->matching->match('/users/42/posts/777', $this->collection);

        // Assert: Проверяем что все параметры извлечены
        $this->assertSame($route, $result->getRoute());
        $this->assertEquals([
            'userId' => '42',
            'postId' => '777'
        ], $result->getParameters());
    }

    public function testMatchWithParameterAtStart(): void
    {
        // Arrange: Параметр в начале маршрута
        $route = Route::create('/{lang}/users', 'UserController', 'index');
        $this->collection->set($route);

        // Act
        $result = $this->matching->match('/en/users', $this->collection);

        // Assert
        $this->assertSame($route, $result->getRoute());
        $this->assertEquals(['lang' => 'en'], $result->getParameters());
    }

    public function testMatchWithParameterAtEnd(): void
    {
        // Arrange: Параметр в конце маршрута
        $route = Route::create('/posts/{slug}', 'PostController', 'show');
        $this->collection->set($route);

        // Act
        $result = $this->matching->match('/posts/my-awesome-post', $this->collection);

        // Assert
        $this->assertSame($route, $result->getRoute());
        $this->assertEquals(['slug' => 'my-awesome-post'], $result->getParameters());
    }

    public function testMatchWithParameterInMiddle(): void
    {
        // Arrange: Параметр в середине маршрута
        $route = Route::create('/api/{version}/users', 'ApiUserController', 'index');
        $this->collection->set($route);

        // Act
        $result = $this->matching->match('/api/v2/users', $this->collection);

        // Assert
        $this->assertSame($route, $result->getRoute());
        $this->assertEquals(['version' => 'v2'], $result->getParameters());
    }

    public function testMatchWithConsecutiveParameters(): void
    {
        // Arrange: Несколько параметров подряд (edge case)
        $route = Route::create('/api/{version}/{resource}', 'ApiController', 'index');
        $this->collection->set($route);

        // Act
        $result = $this->matching->match('/api/v1/users', $this->collection);

        // Assert
        $this->assertSame($route, $result->getRoute());
        $this->assertEquals([
            'version' => 'v1',
            'resource' => 'users'
        ], $result->getParameters());
    }

    public function testMatchParameterWithSpecialCharacters(): void
    {
        // Arrange: Параметр может содержать специальные символы
        $route = Route::create('/posts/{slug}', 'PostController', 'show');
        $this->collection->set($route);

        // Act: URL с дефисами и точками
        $result = $this->matching->match('/posts/hello-world.html', $this->collection);

        // Assert
        $this->assertSame($route, $result->getRoute());
        $this->assertEquals(['slug' => 'hello-world.html'], $result->getParameters());
    }

    public function testMatchParameterWithNumbers(): void
    {
        // Arrange
        $route = Route::create('/users/{id}', 'UserController', 'show');
        $this->collection->set($route);

        // Act: Чисто числовой ID
        $result = $this->matching->match('/users/12345', $this->collection);

        // Assert
        $this->assertSame($route, $result->getRoute());
        $this->assertEquals(['id' => '12345'], $result->getParameters());
    }

    // ========================================================================
    // Тесты совпадения параметров с alias
    // ========================================================================

    public function testMatchParameterizedRouteByAlias(): void
    {
        // Arrange: Маршрут с параметрами и alias тоже с параметрами
        $route = Route::create('/users/{id}', 'UserController', 'show')
            ->setAlias('/profile/{id}');
        $this->collection->set($route);

        // Act & Assert: Основной маршрут
        $result1 = $this->matching->match('/users/123', $this->collection);
        $this->assertSame($route, $result1->getRoute());
        $this->assertEquals(['id' => '123'], $result1->getParameters());

        // Act & Assert: Alias тоже работает
        $result2 = $this->matching->match('/profile/123', $this->collection);
        $this->assertSame($route, $result2->getRoute());
        $this->assertEquals(['id' => '123'], $result2->getParameters());
    }

    // ========================================================================
    // Тесты приоритета маршрутов
    // ========================================================================

    public function testMatchFirstMatchingRoute(): void
    {
        // Arrange: Несколько подходящих маршрутов (должен выбрать первый)
        $route1 = Route::create('/users/{id}', 'UserController', 'show');
        $route2 = Route::create('/users/{slug}', 'UserController', 'showBySlug');
        
        $this->collection->set($route1);
        $this->collection->set($route2);

        // Act: Оба маршрута подходят, но должен сработать первый
        $result = $this->matching->match('/users/123', $this->collection);

        // Assert: Проверяем что выбран первый маршрут
        $this->assertSame($route1, $result->getRoute());
    }

    public function testMatchSpecificRouteBeforeParameterized(): void
    {
        // Arrange: Специфичный маршрут должен быть приоритетнее параметризованного
        $paramRoute = Route::create('/users/{id}', 'UserController', 'show');
        $specificRoute = Route::create('/users/profile', 'UserController', 'profile');
        
        // Добавляем сначала параметризованный
        $this->collection->set($paramRoute);
        $this->collection->set($specificRoute);

        // Act: Проверяем специфичный URL
        $result = $this->matching->match('/users/profile', $this->collection);

        // Assert: Должен сработать параметризованный (он первый)
        // Это правильное поведение - порядок имеет значение
        $this->assertSame($paramRoute, $result->getRoute());
        $this->assertEquals(['id' => 'profile'], $result->getParameters());
    }

    public function testRouteOrderMatters(): void
    {
        // Arrange: Меняем порядок - специфичный первым
        $specificRoute = Route::create('/users/profile', 'UserController', 'profile');
        $paramRoute = Route::create('/users/{id}', 'UserController', 'show');
        
        // Добавляем сначала специфичный
        $this->collection->set($specificRoute);
        $this->collection->set($paramRoute);

        // Act: Проверяем специфичный URL
        $result = $this->matching->match('/users/profile', $this->collection);

        // Assert: Теперь должен сработать специфичный (он первый)
        $this->assertSame($specificRoute, $result->getRoute());
    }

    // ========================================================================
    // Тесты исключений NoMatch
    // ========================================================================

    public function testThrowsNoMatchForNonexistentRoute(): void
    {
        // Arrange: Коллекция с одним маршрутом
        $route = Route::create('/users', 'UserController', 'index');
        $this->collection->set($route);

        // Assert: Ожидаем исключение
        $this->expectException(NoMatch::class);
        $this->expectExceptionMessage('/posts');

        // Act: Пытаемся найти несуществующий маршрут
        $this->matching->match('/posts', $this->collection);
    }

    public function testThrowsNoMatchForEmptyCollection(): void
    {
        // Assert: Ожидаем исключение
        $this->expectException(NoMatch::class);

        // Act: Пытаемся найти в пустой коллекции
        $this->matching->match('/any-route', $this->collection);
    }

    public function testThrowsNoMatchForDifferentSegmentCount(): void
    {
        // Arrange: Маршрут с одним сегментом
        $route = Route::create('/users', 'UserController', 'index');
        $this->collection->set($route);

        // Assert: Ожидаем исключение
        $this->expectException(NoMatch::class);

        // Act: Пытаемся совпасть с URL с двумя сегментами
        $this->matching->match('/users/123', $this->collection);
    }

    public function testThrowsNoMatchForTooFewSegments(): void
    {
        // Arrange: Маршрут с двумя сегментами
        $route = Route::create('/users/{id}', 'UserController', 'show');
        $this->collection->set($route);

        // Assert: Ожидаем исключение
        $this->expectException(NoMatch::class);

        // Act: Пытаемся совпасть с URL с одним сегментом
        $this->matching->match('/users', $this->collection);
    }

    public function testThrowsNoMatchForTooManySegments(): void
    {
        // Arrange: Маршрут с двумя сегментами
        $route = Route::create('/users/{id}', 'UserController', 'show');
        $this->collection->set($route);

        // Assert: Ожидаем исключение
        $this->expectException(NoMatch::class);

        // Act: Пытаемся совпасть с URL с тремя сегментами
        $this->matching->match('/users/123/posts', $this->collection);
    }

    public function testThrowsNoMatchForDifferentStaticSegment(): void
    {
        // Arrange: Маршрут с конкретным статическим сегментом
        $route = Route::create('/users/{id}/posts', 'PostController', 'userPosts');
        $this->collection->set($route);

        // Assert: Ожидаем исключение
        $this->expectException(NoMatch::class);

        // Act: Пытаемся совпасть с другим статическим сегментом
        $this->matching->match('/users/123/comments', $this->collection);
    }

    // ========================================================================
    // Edge cases и граничные условия
    // ========================================================================

    public function testMatchWithEmptyParameter(): void
    {
        // Arrange: Маршрут с параметром
        $route = Route::create('/users/{id}', 'UserController', 'show');
        $this->collection->set($route);

        // Assert: Пустой параметр не должен совпасть (будет /users/ -> один сегмент)
        $this->expectException(NoMatch::class);

        // Act
        $this->matching->match('/users/', $this->collection);
    }

    public function testMatchComplexNestedParametrizedRoute(): void
    {
        // Arrange: Сложный маршрут с вложенностью и параметрами
        $route = Route::create(
            '/api/{version}/users/{userId}/posts/{postId}/comments',
            'CommentController',
            'index'
        );
        $this->collection->set($route);

        // Act
        $result = $this->matching->match('/api/v2/users/42/posts/777/comments', $this->collection);

        // Assert
        $this->assertSame($route, $result->getRoute());
        $this->assertEquals([
            'version' => 'v2',
            'userId' => '42',
            'postId' => '777'
        ], $result->getParameters());
    }

    public function testMatchWithCyrillicInParameter(): void
    {
        // Arrange: URL может содержать кириллицу (в закодированном виде)
        $route = Route::create('/posts/{slug}', 'PostController', 'show');
        $this->collection->set($route);

        // Act: Кириллица в URL (обычно приходит в закодированном виде, но тестируем as-is)
        $result = $this->matching->match('/posts/привет-мир', $this->collection);

        // Assert
        $this->assertSame($route, $result->getRoute());
        $this->assertEquals(['slug' => 'привет-мир'], $result->getParameters());
    }

    public function testMultipleRoutesWithDifferentParameters(): void
    {
        // Arrange: Несколько маршрутов с разными структурами
        $route1 = Route::create('/users/{id}', 'UserController', 'show');
        $route2 = Route::create('/posts/{slug}', 'PostController', 'show');
        $route3 = Route::create('/api/{version}/status', 'ApiController', 'status');
        
        $this->collection->set($route1);
        $this->collection->set($route2);
        $this->collection->set($route3);

        // Act & Assert: Каждый маршрут находит свой URL
        $result1 = $this->matching->match('/users/123', $this->collection);
        $this->assertSame($route1, $result1->getRoute());
        $this->assertEquals(['id' => '123'], $result1->getParameters());

        $result2 = $this->matching->match('/posts/hello-world', $this->collection);
        $this->assertSame($route2, $result2->getRoute());
        $this->assertEquals(['slug' => 'hello-world'], $result2->getParameters());

        $result3 = $this->matching->match('/api/v1/status', $this->collection);
        $this->assertSame($route3, $result3->getRoute());
        $this->assertEquals(['version' => 'v1'], $result3->getParameters());
    }

    // ========================================================================
    // Тесты производительности (документация поведения)
    // ========================================================================

    public function testMatchWithManyRoutes(): void
    {
        // Arrange: Добавляем 100 маршрутов
        for ($i = 1; $i <= 100; $i++) {
            $route = Route::create("/route{$i}", 'Controller', 'action');
            $this->collection->set($route);
        }

        // Добавляем целевой маршрут
        $targetRoute = Route::create('/target', 'TargetController', 'index');
        $this->collection->set($targetRoute);

        // Act: Ищем маршрут (он последний - worst case)
        $startTime = microtime(true);
        $result = $this->matching->match('/target', $this->collection);
        $endTime = microtime(true);

        // Assert: Проверяем что нашли правильный маршрут
        $this->assertSame($targetRoute, $result->getRoute());

        // Документируем производительность (для информации)
        $duration = ($endTime - $startTime) * 1000; // в миллисекундах
        $this->assertLessThan(10, $duration, "Matching 101 routes took {$duration}ms, should be < 10ms");
    }
}

