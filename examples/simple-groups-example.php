<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;

/**
 * Простой пример группировки роутов
 */

class UserController
{
    public function index(): void
    {
        echo "Список пользователей";
    }

    public function show(): void
    {
        echo "Показать пользователя";
    }

    public function create(): void
    {
        echo "Создать пользователя";
    }
}

class AdminController
{
    public function dashboard(): void
    {
        echo "Админ панель";
    }

    public function users(): void
    {
        echo "Управление пользователями";
    }
}

// Создаем коллекцию
$routes = new RoutesCollection();

// === 1. ПРОСТАЯ ГРУППИРОВКА ===
$routes->prefix('/basic')->group(function ($group) {
    $group->get('/users', UserController::class, 'index');
    $group->post('/users', UserController::class, 'create');
    $group->get('/users/{id}', UserController::class, 'show');
});

// === 2. ВЛОЖЕННАЯ ГРУППИРОВКА API V1 ===
$routes->prefix('/api')->group(function ($api) {
    $api->prefix('/v1')->group(function ($v1) {
        $v1->get('/users', UserController::class, 'index');
        $v1->post('/users', UserController::class, 'create');
    });
});

// === 3. ВЛОЖЕННАЯ ГРУППИРОВКА API V2 ===
$routes->prefix('/api')->group(function ($api) {
    $api->prefix('/v2')->group(function ($v2) {
        $v2->get('/users', UserController::class, 'index');
        $v2->get('/users/{id}', UserController::class, 'show');
    });
});

// === 4. ГРУППИРОВКА С MIDDLEWARE ===
use FaustVik\Router\Middleware\LoggingMiddleware;

$routes->middleware([LoggingMiddleware::class])
    ->prefix('/admin')
    ->group(function ($group) {
        $group->get('/dashboard', AdminController::class, 'dashboard');
        $group->get('/users', AdminController::class, 'users');
    });

// Запускаем роутер
$router = new Router();

try {
    $router->setCollection($routes)->run();
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage();
}

// Показываем все маршруты
echo "\n\n=== СОЗДАННЫЕ МАРШРУТЫ ===\n";
foreach ($routes->get() as $index => $route) {
    echo sprintf(
        "%d. %-6s %-25s -> %s@%s\n",
        $index + 1,
        implode('|', $route->getMethods()),
        $route->getRoute(),
        $route->getClass(),
        $route->getAction()
    );
}

echo "\n=== ТЕСТИРОВАНИЕ ===\n";
echo "Примеры команд:\n";
echo 'REQUEST_METHOD="GET" REQUEST_URI="/basic/users" php ' . __FILE__ . "\n";
echo 'REQUEST_METHOD="GET" REQUEST_URI="/api/v1/users" php ' . __FILE__ . "\n";
echo 'REQUEST_METHOD="GET" REQUEST_URI="/api/v2/users/123" php ' . __FILE__ . "\n";
echo 'REQUEST_METHOD="GET" REQUEST_URI="/admin/dashboard" php ' . __FILE__ . "\n";
