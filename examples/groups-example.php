<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;
use FaustVik\Router\Middleware\AuthMiddleware;
use FaustVik\Router\Middleware\LoggingMiddleware;
use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;

/**
 * Контроллеры для демонстрации
 */
class HomeController
{
    public function index(): void
    {
        echo "Главная страница";
    }

    public function about(): void
    {
        echo "О нас";
    }
}

class ApiController
{
    public function users(): void
    {
        echo json_encode([
            'users' => [
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane']
            ]
        ]);
    }

    public function posts(): void
    {
        echo json_encode([
            'posts' => [
                ['id' => 1, 'title' => 'Hello World'],
                ['id' => 2, 'title' => 'PHP Router']
            ]
        ]);
    }
}

class AdminController
{
    public function dashboard(): void
    {
        echo "Админ панель - Главная";
    }

    public function users(): void
    {
        echo "Админ панель - Управление пользователями";
    }

    public function settings(): void
    {
        echo "Админ панель - Настройки";
    }
}

class BlogController
{
    public function index(): void
    {
        echo "Список статей блога";
    }

    public function show(Request $request): void
    {
        $id = $request->getRouteParam('id');
        echo "Статья блога #{$id}";
    }

    public function category(Request $request): void
    {
        $category = $request->getRouteParam('category');
        echo "Статьи в категории: {$category}";
    }
}

// Создаем коллекцию маршрутов
$routes = new RoutesCollection();

// === 1. ОБЫЧНЫЕ МАРШРУТЫ (без группировки) ===
$routes->addGet('/', HomeController::class, 'index');
$routes->addGet('/about', HomeController::class, 'about');

// === 2. ГРУППИРОВКА С ПРЕФИКСОМ ===
$routes->prefix('/api')->group(function ($group) {
    $group->get('/users', ApiController::class, 'users');
    $group->get('/posts', ApiController::class, 'posts');

    // Вложенная группа для версионирования API
    $group->prefix('/v1')->group(function ($v1) {
        $v1->get('/users', ApiController::class, 'users');
        $v1->post('/users', ApiController::class, 'createUser');

        $v1->prefix('/admin')->group(function ($admin) {
            $admin->get('/stats', ApiController::class, 'adminStats');
        });
    });
});

// === 3. ГРУППИРОВКА С MIDDLEWARE ===
$routes->middleware([LoggingMiddleware::class])
    ->prefix('/admin')
    ->group(function ($group) {
        // Эти маршруты будут иметь LoggingMiddleware
        $group->get('/dashboard', AdminController::class, 'dashboard');

        // Добавляем еще middleware для защищенных маршрутов
        $group->middleware([AuthMiddleware::class])->group(function ($authGroup) {
            $authGroup->get('/users', AdminController::class, 'users');
            $authGroup->get('/settings', AdminController::class, 'settings');
            $authGroup->delete('/users/{id}', AdminController::class, 'deleteUser');
        });
    });

// === 4. ГРУППИРОВКА ДЛЯ БЛОГА С АНОНИМНЫМИ ФУНКЦИЯМИ ===
$routes->prefix('/blog')->group(function ($group) {
    $group->get('/', BlogController::class, 'index');
    $group->get('/category/{category}', BlogController::class, 'category');
    $group->get('/post/{id}', BlogController::class, 'show');

    // Анонимные функции в группе
    $group->getFunc('/rss', function (Request $request): Response {
        return Response::create('<?xml version="1.0"?><rss>RSS Feed</rss>')
            ->withHeader('Content-Type', 'application/rss+xml');
    });

    $group->getFunc('/sitemap.xml', function (): Response {
        return Response::create('<?xml version="1.0"?><urlset>Sitemap</urlset>')
            ->withHeader('Content-Type', 'application/xml');
    });
});

// === 5. ГРУППИРОВКА БЕЗ ПРЕФИКСА (ТОЛЬКО MIDDLEWARE) ===
$routes->middleware([LoggingMiddleware::class])->group(function ($group) {
    $group->getFunc('/health', function (): Response {
        return Response::json(['status' => 'ok', 'timestamp' => time()]);
    });

    $group->getFunc('/version', function (): Response {
        return Response::json(['version' => '2.0-alpha', 'build' => date('Y-m-d')]);
    });
});

// === 6. СЛОЖНАЯ ВЛОЖЕННАЯ ГРУППИРОВКА ===
$routes->prefix('/api')->group(function ($api) {
    $api->prefix('/v2')->middleware([LoggingMiddleware::class])->group(function ($v2) {
        $v2->getFunc('/info', function (): Response {
            return Response::json(['api_version' => 'v2']);
        });

        $v2->prefix('/users')->middleware([AuthMiddleware::class])->group(function ($users) {
            $users->getFunc('/', function (): Response {
                return Response::json(['message' => 'Список пользователей API v2']);
            });

            $users->getFunc('/{id}', function (Request $request): Response {
                $id = $request->getRouteParam('id');
                return Response::json(['user_id' => $id, 'api_version' => 'v2']);
            });

            $users->prefix('/{id}')->group(function ($userActions) {
                $userActions->getFunc('/posts', function (Request $request): Response {
                    $id = $request->getRouteParam('id');
                    return Response::json(['user_id' => $id, 'posts' => []]);
                });

                $userActions->getFunc('/comments', function (Request $request): Response {
                    $id = $request->getRouteParam('id');
                    return Response::json(['user_id' => $id, 'comments' => []]);
                });
            });
        });
    });
});

// Создаем и запускаем роутер
$router = new Router();

try {
    $router->setCollection($routes)->run();
} catch (Exception $e) {
    // Обработка ошибок
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage()
    ]);
}

// === ИНФОРМАЦИЯ О МАРШРУТАХ ===
echo "\n\n=== МАРШРУТЫ В КОЛЛЕКЦИИ ===\n";
foreach ($routes->get() as $index => $route) {
    echo sprintf(
        "%d. %s %s -> %s\n",
        $index + 1,
        implode('|', $route->getMethods()),
        $route->getRoute(),
        $route instanceof \FaustVik\Router\Route\Route ? $route->getClass() . '@' . $route->getAction() : 'Anonymous Function'
    );
}

echo "\n=== ТЕСТИРОВАНИЕ ===\n";
echo "Попробуйте следующие URL:\n";
echo "- GET /                     -> Главная страница\n";
echo "- GET /api/users            -> API пользователи\n";
echo "- GET /api/v1/users         -> API v1 пользователи\n";
echo "- GET /admin/dashboard      -> Админ панель\n";
echo "- GET /blog/                -> Блог\n";
echo "- GET /blog/post/123        -> Статья блога\n";
echo "- GET /blog/rss             -> RSS лента\n";
echo "- GET /health               -> Статус системы\n";
echo "- GET /api/v2/users/123     -> API v2 пользователь\n";
echo "- GET /api/v2/users/123/posts -> Посты пользователя\n";

echo "\n=== КОМАНДЫ ДЛЯ ТЕСТИРОВАНИЯ ===\n";
echo 'REQUEST_METHOD="GET" REQUEST_URI="/" php ' . __FILE__ . "\n";
echo 'REQUEST_METHOD="GET" REQUEST_URI="/api/users" php ' . __FILE__ . "\n";
echo 'REQUEST_METHOD="GET" REQUEST_URI="/admin/dashboard" php ' . __FILE__ . "\n";
echo 'REQUEST_METHOD="GET" REQUEST_URI="/blog/post/123" php ' . __FILE__ . "\n";
