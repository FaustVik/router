<?php

declare(strict_types=1);

use FaustVik\Router\Cache\FileCache;
use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;

require_once dirname(__DIR__) . '/vendor/autoload.php';

echo "=== Пример использования кеширования в роутере ===\n\n";

// Создаем роутер
$router = new Router();

// Создаем коллекцию маршрутов
$routesCollection = new RoutesCollection();

// Добавляем много маршрутов для демонстрации кеша
for ($i = 1; $i <= 10; $i++) {
    $routesCollection->set(
        Route::create("/user/{id}/profile/{section}", UserController::class, 'profile', [], ['GET'])
    );
    $routesCollection->set(
        Route::create("/api/v1/users/{id}", UserController::class, 'getUser', [], ['GET'])
    );
    $routesCollection->set(
        Route::create("/api/v1/users/{id}/posts", UserController::class, 'getUserPosts', [], ['GET'])
    );
}

// Создаем маршрут с параметрами
$route = Route::create("/user/{id}/profile/{section}", UserController::class, 'profile', [], ['GET']);
$routesCollection->set($route);

// Устанавливаем коллекцию маршрутов
$router->setCollection($routesCollection);

// === Без кеширования ===
echo "1. Работа БЕЗ кеширования:\n";
$router->disableCache();

$startTime = microtime(true);
$router->setUri('/user/123/profile/settings');
$router->run();
$endTime = microtime(true);

echo "Время выполнения без кеша: " . round(($endTime - $startTime) * 1000, 2) . " мс\n";
echo "Статус кеша: " . ($router->isCacheEnabled() ? "включен" : "отключен") . "\n\n";

// === С кешированием ===
echo "2. Работа С кешированием:\n";

// Включаем кеш
$router->enableCache();

// Настраиваем кеш (опционально)
$cache = new FileCache('cache', 'app_cache_');
$router->setCache($cache);

echo "Первый запрос (кеш пустой):\n";
$startTime = microtime(true);
$router->setUri('/user/123/profile/settings');
$router->run();
$endTime = microtime(true);

echo "Время выполнения: " . round(($endTime - $startTime) * 1000, 2) . " мс\n";
echo "Статус кеша: " . ($router->isCacheEnabled() ? "включен" : "отключен") . "\n\n";

echo "Второй запрос (из кеша):\n";
$startTime = microtime(true);
$router->setUri('/user/123/profile/settings');
$router->run();
$endTime = microtime(true);

echo "Время выполнения: " . round(($endTime - $startTime) * 1000, 2) . " мс\n";
echo "Статус кеша: " . ($router->isCacheEnabled() ? "включен" : "отключен") . "\n\n";

// === Управление кешем ===
echo "3. Управление кешем:\n";

// Получаем кеш
$cacheInstance = $router->getCache();
if ($cacheInstance) {
    echo "Кеш-драйвер: " . get_class($cacheInstance) . "\n";

    // Проверяем существование в кеше
    $cacheKey = $router->getCacheKey();
    echo "Кеш-ключ: " . $cacheKey . "\n";

    // Очищаем кеш
    echo "Очищаем кеш роутов...\n";
    $router->clearRouteCache();
    echo "Кеш очищен.\n";
}

// === Разные типы кеша ===
echo "\n4. Настройка кеша:\n";

// Создаем кеш с другими настройками
$customCache = new FileCache('custom_cache', 'my_app_');
$router->setCache($customCache);

echo "Установлен кастомный кеш в папку 'custom_cache'\n";

// Проверяем работу
$router->setUri('/user/456/profile/account');
$router->run();

echo "Маршрут обработан с кастомным кешем\n";

// Контроллер для демонстрации
class UserController
{
    public function profile(string $id, string $section): void
    {
        echo "Вызван метод profile с параметрами: id=$id, section=$section\n";
    }

    public function getUser(string $id): void
    {
        echo "Вызван метод getUser с параметрами: id=$id\n";
    }

    public function getUserPosts(string $id): void
    {
        echo "Вызван метод getUserPosts с параметрами: id=$id\n";
    }
}

echo "\n=== Пример завершен ===\n";
