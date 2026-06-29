<?php

declare(strict_types=1);

/**
 * Named Routes и URL Generation Example
 *
 * Этот пример демонстрирует:
 * 1. Именование маршрутов с помощью метода name()
 * 2. Генерацию URL по имени маршрута
 * 3. Подстановку параметров в URL
 * 4. Опциональные параметры
 * 5. Constraints для параметров
 * 6. Проверку существования маршрута
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Http\Response;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;

// ============================================================================
// Controllers
// ============================================================================

class HomeController
{
    public function index(): void
    {
        echo "<h1>Главная страница</h1>\n";
        echo "<ul>\n";
        echo "  <li><a href='/users'>Список пользователей</a></li>\n";
        echo "  <li><a href='/posts'>Все посты</a></li>\n";
        echo "  <li><a href='/posts/2025'>Посты за 2025 год</a></li>\n";
        echo "</ul>\n";
    }
}

class UserController
{
    public function __construct(private Router $router)
    {
    }

    public function index(): void
    {
        echo "<h1>Список пользователей</h1>\n";
        echo "<ul>\n";

        // Генерируем URL для каждого пользователя
        for ($i = 1; $i <= 5; $i++) {
            $url = $this->router->url('users.show', ['id' => $i]);
            echo "  <li><a href='{$url}'>Пользователь #{$i}</a></li>\n";
        }

        echo "</ul>\n";
    }

    public function show($id): void
    {
        echo "<h1>Профиль пользователя #{$id}</h1>\n";

        // Генерируем URL для редактирования
        $editUrl = $this->router->url('users.edit', ['id' => $id]);
        echo "<p><a href='{$editUrl}'>Редактировать профиль</a></p>\n";

        // Генерируем URL для постов пользователя
        $postsUrl = $this->router->url('users.posts', ['userId' => $id]);
        echo "<p><a href='{$postsUrl}'>Посты пользователя</a></p>\n";

        // Ссылка на главную
        $homeUrl = $this->router->url('home');
        echo "<p><a href='{$homeUrl}'>На главную</a></p>\n";
    }

    public function edit($id): void
    {
        echo "<h1>Редактирование пользователя #{$id}</h1>\n";

        $showUrl = $this->router->url('users.show', ['id' => $id]);
        echo "<p><a href='{$showUrl}'>Отмена</a></p>\n";
    }

    public function posts($userId): void
    {
        echo "<h1>Посты пользователя #{$userId}</h1>\n";

        // Генерируем URL для отдельных постов
        for ($i = 1; $i <= 3; $i++) {
            $url = $this->router->url('users.posts.show', [
                'userId' => $userId,
                'postId' => $i,
            ]);
            echo "<p><a href='{$url}'>Пост #{$i}</a></p>\n";
        }
    }

    public function showPost($userId, $postId): void
    {
        echo "<h1>Пост #{$postId} от пользователя #{$userId}</h1>\n";

        $userUrl = $this->router->url('users.show', ['id' => $userId]);
        echo "<p><a href='{$userUrl}'>К профилю автора</a></p>\n";
    }
}

class PostController
{
    public function __construct(private Router $router)
    {
    }

    public function index($year = null, $month = null): void
    {
        if ($year && $month) {
            echo "<h1>Посты за {$month}/{$year}</h1>\n";
        } elseif ($year) {
            echo "<h1>Посты за {$year} год</h1>\n";

            // Генерируем ссылки на месяцы
            echo "<h2>Посты по месяцам:</h2>\n";
            for ($m = 1; $m <= 12; $m++) {
                $url = $this->router->url('posts.archive', [
                    'year' => $year,
                    'month' => $m,
                ]);
                echo "<p><a href='{$url}'>Месяц {$m}</a></p>\n";
            }
        } else {
            echo "<h1>Все посты</h1>\n";

            // Ссылки на архивы по годам
            echo "<h2>Архив по годам:</h2>\n";
            for ($y = 2023; $y <= 2025; $y++) {
                $url = $this->router->url('posts.archive', ['year' => $y]);
                echo "<p><a href='{$url}'>Посты за {$y} год</a></p>\n";
            }
        }

        $homeUrl = $this->router->url('home');
        echo "<p><a href='{$homeUrl}'>На главную</a></p>\n";
    }

    public function show($slug): void
    {
        echo "<h1>Пост: {$slug}</h1>\n";

        $postsUrl = $this->router->url('posts.index');
        echo "<p><a href='{$postsUrl}'>Все посты</a></p>\n";
    }
}

class ApiController
{
    public function __construct(private Router $router)
    {
    }

    public function index(): void
    {
        $endpoints = [
            'users.list' => $this->router->url('api.users.index'),
            'users.show' => $this->router->url('api.users.show', ['id' => 1]),
            'posts.list' => $this->router->url('api.posts.index'),
            'posts.show' => $this->router->url('api.posts.show', ['id' => 1]),
        ];

        Response::json([
            'message' => 'API v1',
            'endpoints' => $endpoints,
            'documentation' => $this->router->url('api.docs'),
        ])->send();
        exit;
    }

    public function docs(): void
    {
        Response::json([
            'version' => '1.0.0',
            'endpoints' => [
                [
                    'name' => 'List Users',
                    'route' => 'api.users.index',
                    'url' => $this->router->url('api.users.index'),
                    'method' => 'GET',
                ],
                [
                    'name' => 'Show User',
                    'route' => 'api.users.show',
                    'url' => $this->router->url('api.users.show', ['id' => '{id}']),
                    'method' => 'GET',
                    'parameters' => ['id' => 'integer'],
                ],
            ],
        ])->send();
        exit;
    }

    public function users(): void
    {
        Response::json([
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Jane Smith'],
        ])->send();
        exit;
    }

    public function showUser($id): void
    {
        Response::json([
            'id' => $id,
            'name' => 'User #' . $id,
            'profile_url' => $this->router->url('users.show', ['id' => $id]),
        ])->send();
        exit;
    }
}

// ============================================================================
// Настройка маршрутов
// ============================================================================

$router = new Router();
$collection = new RoutesCollection();

// Главная страница
$collection->addGet('/', HomeController::class, 'index')
    ->name('home');

// Пользователи с именованными маршрутами
$collection->prefix('/users')->group(function ($users) use ($router): void {
    $users->get('', UserController::class, 'index', [$router])
        ->name('users.index');

    $users->get('/{id}', UserController::class, 'show', [$router])
        ->where('id', '\d+')  // Только цифры
        ->name('users.show');

    $users->get('/{id}/edit', UserController::class, 'edit', [$router])
        ->where('id', '\d+')
        ->name('users.edit');

    $users->get('/{userId}/posts', UserController::class, 'posts', [$router])
        ->where('userId', '\d+')
        ->name('users.posts');

    $users->get('/{userId}/posts/{postId}', UserController::class, 'showPost', [$router])
        ->where('userId', '\d+')
        ->where('postId', '\d+')
        ->name('users.posts.show');
});

// Посты с опциональными параметрами
$collection->prefix('/posts')->group(function ($posts) use ($router): void {
    // Опциональные параметры year и month
    $posts->get('/{year?}/{month?}', PostController::class, 'index', [$router])
        ->where('year', '\d{4}')      // Год: 4 цифры
        ->where('month', '\d{1,2}')   // Месяц: 1-2 цифры
        ->name('posts.archive');

    $posts->get('/view/{slug}', PostController::class, 'show', [$router])
        ->where('slug', '[a-z0-9\-]+')  // slug: буквы, цифры и дефисы
        ->name('posts.show');
});

// Alias для posts.index
$collection->addGet('/posts', PostController::class, 'index', [$router])
    ->name('posts.index');

// API маршруты
$collection->prefix('/api/v1')->group(function ($api) use ($router): void {
    $api->get('', ApiController::class, 'index', [$router])
        ->name('api.index');

    $api->get('/docs', ApiController::class, 'docs', [$router])
        ->name('api.docs');

    $api->get('/users', ApiController::class, 'users', [$router])
        ->name('api.users.index');

    $api->get('/users/{id}', ApiController::class, 'showUser', [$router])
        ->where('id', '\d+')
        ->name('api.users.show');

    $api->get('/posts', ApiController::class, 'users', [$router])  // Просто для примера
        ->name('api.posts.index');

    $api->get('/posts/{id}', ApiController::class, 'showUser', [$router])
        ->where('id', '\d+')
        ->name('api.posts.show');
});

$router->setCollection($collection);

// ============================================================================
// Демонстрация возможностей
// ============================================================================

echo "=== Named Routes и URL Generation Demo ===\n\n";

// 1. Проверка существования маршрута
echo "1. Проверка существования маршрута:\n";
echo "   has('users.show'): " . ($router->has('users.show') ? 'true' : 'false') . "\n";
echo "   has('non.existent'): " . ($router->has('non.existent') ? 'true' : 'false') . "\n\n";

// 2. Генерация простых URL
echo "2. Генерация простых URL:\n";
echo "   url('home') = " . $router->url('home') . "\n";
echo "   url('users.index') = " . $router->url('users.index') . "\n";
echo "   url('posts.index') = " . $router->url('posts.index') . "\n\n";

// 3. Генерация URL с параметрами
echo "3. Генерация URL с параметрами:\n";
echo "   url('users.show', ['id' => 123]) = " . $router->url('users.show', ['id' => 123]) . "\n";
echo "   url('users.posts.show', ['userId' => 5, 'postId' => 42]) = "
    . $router->url('users.posts.show', ['userId' => 5, 'postId' => 42]) . "\n\n";

// 4. Опциональные параметры
echo "4. Опциональные параметры:\n";
echo "   url('posts.archive') = " . $router->url('posts.archive') . "\n";
echo "   url('posts.archive', ['year' => 2025]) = "
    . $router->url('posts.archive', ['year' => 2025]) . "\n";
echo "   url('posts.archive', ['year' => 2025, 'month' => 10]) = "
    . $router->url('posts.archive', ['year' => 2025, 'month' => 10]) . "\n\n";

// 5. Список всех именованных маршрутов
echo "5. Список всех именованных маршрутов:\n";
foreach ($router->getNamedRoutes() as $name => $route) {
    echo "   - {$name}: {$route->getRoute()}\n";
}
echo "\n";

// 6. Constraints валидация
echo "6. Constraints валидация:\n";
try {
    echo "   url('users.show', ['id' => 'abc']) = ";
    echo $router->url('users.show', ['id' => 'abc']) . "\n";
} catch (InvalidArgumentException $e) {
    echo '   ERROR: ' . $e->getMessage() . "\n";
}
echo "\n";

// ============================================================================
// Запуск роутера
// ============================================================================

echo "=== Запуск роутера ===\n\n";

try {
    $router->run();
} catch (Throwable $e) {
    echo 'Ошибка: ' . $e->getMessage() . "\n";
}
