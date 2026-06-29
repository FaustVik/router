<?php

declare(strict_types=1);

use FaustVik\Router\Cache\FileCache;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;

require_once dirname(__DIR__) . '/vendor/autoload.php';

echo "=== Продакшен пример использования кеширования ===\n\n";

// Имитация настроек окружения
$isProduction = true;
$enableCache = $isProduction;
$cacheDir = 'cache/routes';
$cacheTtl = 3600; // 1 час

// Создаем роутер
$router = new Router();

// Настраиваем кеш для продакшена
if ($enableCache) {
    $cache = new FileCache($cacheDir, 'prod_routes_');
    $router->setCache($cache);
    $router->enableCache();

    // Настраиваем TTL через конфигурацию
    $config = $router->getConfig();
    $config->setCacheTtl($cacheTtl);

    echo "✓ Кеширование включено для продакшена\n";
    echo "✓ Папка кеша: $cacheDir\n";
    echo "✓ TTL: $cacheTtl секунд\n\n";
} else {
    echo "✗ Кеширование отключено для разработки\n\n";
}

// Создаем маршруты приложения
$routesCollection = new RoutesCollection();

// API маршруты
$routesCollection->addGet('/api/users', ApiController::class, 'getAllUsers');
$routesCollection->addGet('/api/users/{id}', ApiController::class, 'getUser');
$routesCollection->addPost('/api/users', ApiController::class, 'createUser');
$routesCollection->addPut('/api/users/{id}', ApiController::class, 'updateUser');
$routesCollection->addDelete('/api/users/{id}', ApiController::class, 'deleteUser');

// Веб маршруты
$routesCollection->addGet('/', WebController::class, 'home');
$routesCollection->addGet('/about', WebController::class, 'about');
$routesCollection->addGet('/contact', WebController::class, 'contact');
$routesCollection->addGet('/user/{id}/profile', WebController::class, 'userProfile');
$routesCollection->addGet('/blog/{slug}', WebController::class, 'blogPost');

// Группы маршрутов (без middleware для простоты примера)
$routesCollection->prefix('/admin')
    ->group(function ($group): void {
        $group->get('/dashboard', AdminController::class, 'dashboard');
        $group->get('/users', AdminController::class, 'users');
        $group->get('/settings', AdminController::class, 'settings');
    });

// Устанавливаем коллекцию
$router->setCollection($routesCollection);

// === Симуляция запросов ===
$testUrls = [
    '/api/users/123',
    '/user/456/profile',
    '/blog/my-first-post',
    '/admin/dashboard',
    '/about',
];

foreach ($testUrls as $url) {
    echo "Обрабатываем: $url\n";

    $startTime = microtime(true);

    try {
        $router->setUri($url);
        $router->run();
        $endTime = microtime(true);

        $time = round(($endTime - $startTime) * 1000, 2);
        echo "✓ Время выполнения: {$time} мс\n";

        if ($router->isCacheEnabled()) {
            echo "✓ Результат кеширован\n";
        }
    } catch (Exception $e) {
        echo '✗ Ошибка: ' . $e->getMessage() . "\n";
    }

    echo "---\n";
}

// === Статистика кеша ===
echo "\n=== Статистика кеша ===\n";
$cacheInstance = $router->getCache();
if ($cacheInstance) {
    echo 'Кеш-драйвер: ' . get_class($cacheInstance) . "\n";
    echo 'Статус кеша: ' . ($router->isCacheEnabled() ? 'включен' : 'отключен') . "\n";
    echo 'TTL: ' . $router->getConfig()->getCacheTtl() . " секунд\n";

    // Показываем содержимое кеша
    echo "\nФайлы кеша:\n";
    $cacheFiles = glob($cacheDir . '/*.cache');
    if ($cacheFiles) {
        foreach ($cacheFiles as $file) {
            $size = filesize($file);
            $modified = date('Y-m-d H:i:s', filemtime($file));
            echo '- ' . basename($file) . " ({$size} байт, изменен: {$modified})\n";
        }
    } else {
        echo "Файлы кеша не найдены\n";
    }
}

// === Очистка кеша ===
echo "\n=== Управление кешем ===\n";
if ($router->isCacheEnabled()) {
    echo "Очищаем кеш...\n";
    $router->clearRouteCache();
    echo "✓ Кеш очищен\n";
}

// Контроллеры для демонстрации
class ApiController
{
    public function getAllUsers(): void
    {
        echo "API: Получение всех пользователей\n";
    }

    public function getUser(string $id): void
    {
        echo "API: Получение пользователя ID: $id\n";
    }

    public function createUser(): void
    {
        echo "API: Создание пользователя\n";
    }

    public function updateUser(string $id): void
    {
        echo "API: Обновление пользователя ID: $id\n";
    }

    public function deleteUser(string $id): void
    {
        echo "API: Удаление пользователя ID: $id\n";
    }
}

class WebController
{
    public function home(): void
    {
        echo "Web: Главная страница\n";
    }

    public function about(): void
    {
        echo "Web: О нас\n";
    }

    public function contact(): void
    {
        echo "Web: Контакты\n";
    }

    public function userProfile(string $id): void
    {
        echo "Web: Профиль пользователя ID: $id\n";
    }

    public function blogPost(string $slug): void
    {
        echo "Web: Статья блога: $slug\n";
    }
}

class AdminController
{
    public function dashboard(): void
    {
        echo "Admin: Панель управления\n";
    }

    public function users(): void
    {
        echo "Admin: Управление пользователями\n";
    }

    public function settings(): void
    {
        echo "Admin: Настройки\n";
    }
}

echo "\n=== Пример завершен ===\n";
