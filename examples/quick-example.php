<?php

/**
 * QuickRouter - Пример быстрого старта
 *
 * Демонстрирует простейшее использование роутера.
 * Hello World за 5 минут!
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Router\QuickRouter;
use FaustVik\Router\Http\Response;

// ============================================================================
// Простейший пример - анонимные функции
// ============================================================================

echo "=== QuickRouter Example ===\n\n";

// Создаем роутер - всё готово к работе!
// По умолчанию cache и DI отключены для простоты
$app = new QuickRouter();

// ✨ Можно включить опции через named arguments (PHP 8.0+):
// $app = new QuickRouter(cache: true);
// $app = new QuickRouter(di: true);
// $app = new QuickRouter(cache: true, di: true);

// Домашняя страница
$app->get('/', function () {
    return Response::html('<h1>Welcome to QuickRouter!</h1>');
});

// Простой текст
$app->get('/hello', function () {
    echo "Hello World!";
});

// С параметрами из URL
$app->get('/hello/{name}', function ($name) {
    echo "Hello, " . htmlspecialchars($name) . "!";
});

// JSON ответ
$app->get('/api/users/{id}', function ($id) {
    return Response::json([
        'id' => (int) $id,
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);
});

// Несколько параметров
$app->get('/posts/{category}/{id}', function ($category, $id) {
    echo "Category: $category, Post ID: $id";
});

// ============================================================================
// POST запросы
// ============================================================================

$app->post('/users', function () {
    return Response::json([
        'message' => 'User created',
        'id' => 123
    ], 201);
});

// ============================================================================
// Простые API маршруты (без групп для упрощения примера)
// ============================================================================

$app->get('/api/status', fn() => Response::json(['status' => 'ok']));
$app->get('/api/version', fn() => Response::json(['version' => '1.0.0']));
$app->get('/api/v2/status', fn() => Response::json(['status' => 'ok', 'version' => 2]));

// ============================================================================
// Использование с контроллерами (если есть)
// ============================================================================

// Простой контроллер для примера
class SimpleController
{
    public function index()
    {
        echo "Controller Index Page";
    }

    public function show($id)
    {
        echo "Showing item #$id from controller";
    }
}

$app->get('/controller', [SimpleController::class, 'index']);
$app->get('/controller/{id}', [SimpleController::class, 'show']);

// ============================================================================
// Доступ к расширенному API (когда нужно больше возможностей)
// ============================================================================

// Пример: включаем кеш через advanced API
if (getenv('APP_ENV') === 'production') {
    $app->advanced()->enableCache();
}

// ============================================================================
// Информация о доступных маршрутах
// ============================================================================

echo "Available routes:\n";
echo "  GET  /\n";
echo "  GET  /hello\n";
echo "  GET  /hello/{name}\n";
echo "  GET  /api/users/{id}\n";
echo "  GET  /posts/{category}/{id}\n";
echo "  POST /users\n";
echo "  GET  /api/status\n";
echo "  GET  /api/version\n";
echo "  GET  /api/v2/status\n";
echo "  GET  /controller\n";
echo "  GET  /controller/{id}\n";
echo "\n";

echo "Testing routes:\n\n";

// ============================================================================
// Тестирование маршрутов
// ============================================================================

// Тест 1: Главная страница
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
echo "Test 1: GET /\n";
$app->run();
echo "\n\n";

// Создаем новый экземпляр для следующего теста
$app = new QuickRouter();
setupRoutes($app);

// Тест 2: Hello с параметром
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/hello/Victor';
echo "Test 2: GET /hello/Victor\n";
$app->run();
echo "\n\n";

// Создаем новый экземпляр для следующего теста
$app = new QuickRouter();
setupRoutes($app);

// Тест 3: API endpoint
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/users/42';
echo "Test 3: GET /api/users/42\n";
$app->run();
echo "\n\n";

// Создаем новый экземпляр для следующего теста
$app = new QuickRouter();
setupRoutes($app);

// Тест 4: Вложенные параметры
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/posts/technology/123';
echo "Test 4: GET /posts/technology/123\n";
$app->run();
echo "\n\n";

// ============================================================================
// Helper функция для настройки маршрутов
// ============================================================================

function setupRoutes(QuickRouter $app): void
{
    $app->get('/', function () {
        return Response::html('<h1>Welcome to QuickRouter!</h1>');
    });

    $app->get('/hello/{name}', function ($name) {
        echo "Hello, " . htmlspecialchars($name) . "!";
    });

    $app->get('/api/users/{id}', function ($id) {
        return Response::json([
            'id' => (int) $id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
    });

    $app->get('/posts/{category}/{id}', function ($category, $id) {
        echo "Category: $category, Post ID: $id";
    });
}

// ============================================================================
// Инструкции по запуску
// ============================================================================

echo "=== How to use ===\n";
echo "\n";
echo "1. Run from command line:\n";
echo "   php examples/quick-example.php\n";
echo "\n";
echo "2. Test specific routes:\n";
echo "   REQUEST_METHOD=GET REQUEST_URI=/hello/John php examples/quick-example.php\n";
echo "\n";
echo "3. Run with built-in server:\n";
echo "   php -S localhost:8000 examples/quick-example.php\n";
echo "   Then open: http://localhost:8000/hello/World\n";
echo "\n";
