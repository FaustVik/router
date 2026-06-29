<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;

/**
 * Продвинутый пример использования Named Routes
 *
 * Демонстрирует:
 * - Генерацию URL по имени маршрута
 * - Query параметры
 * - Якоря (фрагменты)
 * - Опциональные параметры
 * - Constraints для параметров
 */
echo "=== Продвинутая генерация URL с Named Routes ===\n\n";

$router = new Router();
$routes = new RoutesCollection();

// Создаем маршруты с именами
$routes->set(
    Route::create('/users', 'UserController', 'index', methods: ['GET'])
        ->name('users.index')
);

$routes->set(
    Route::create('/users/{id}', 'UserController', 'show', methods: ['GET'])
        ->name('users.show')
        ->where('id', '\d+') // Только цифры
);

$routes->set(
    Route::create('/posts/{slug}', 'PostController', 'show', methods: ['GET'])
        ->name('posts.show')
);

$routes->set(
    Route::create('/blog/{category?}', 'BlogController', 'index', methods: ['GET'])
        ->name('blog.index')
);

$routes->set(
    Route::create('/search', 'SearchController', 'index', methods: ['GET'])
        ->name('search')
);

$routes->set(
    Route::create('/docs/{section}/{page?}', 'DocsController', 'show', methods: ['GET'])
        ->name('docs.show')
);

$router->setCollection($routes);

echo "=== 1. Базовая генерация URL ===\n\n";

// Простой маршрут без параметров
$url = $router->url('users.index');
echo "users.index: $url\n";
// => /users

// Маршрут с обязательным параметром
$url = $router->url('users.show', ['id' => 123]);
echo "users.show: $url\n";
// => /users/123

// Маршрут с параметром-строкой
$url = $router->url('posts.show', ['slug' => 'hello-world']);
echo "posts.show: $url\n";
// => /posts/hello-world

echo "\n=== 2. Query параметры ===\n\n";

// Добавляем query параметры
$url = $router->url('users.index', [], ['page' => 2, 'sort' => 'name']);
echo "users.index с пагинацией: $url\n";
// => /users?page=2&sort=name

// Query параметры с существующими path параметрами
$url = $router->url('users.show', ['id' => 123], ['edit' => 'true', 'tab' => 'profile']);
echo "users.show с query: $url\n";
// => /users/123?edit=true&tab=profile

// Поисковый запрос
$url = $router->url('search', [], ['q' => 'php router', 'category' => 'tutorials']);
echo "search: $url\n";
// => /search?q=php+router&category=tutorials

echo "\n=== 3. Якоря (фрагменты) ===\n\n";

// URL с якорем
$url = $router->url('posts.show', ['slug' => 'my-post'], [], 'comments');
echo "posts.show с якорем: $url\n";
// => /posts/my-post#comments

// URL с якорем, содержащим спецсимволы
$url = $router->url('docs.show', ['section' => 'api', 'page' => 'routes'], [], 'named routes');
echo "docs.show с якорем и пробелом: $url\n";
// => /docs/api/routes#named%20routes

echo "\n=== 4. Полный пример (path + query + якорь) ===\n\n";

// Все вместе
$url = $router->url(
    name: 'posts.show',
    params: ['slug' => 'advanced-routing'],
    query: ['ref' => 'homepage', 'utm_source' => 'newsletter'],
    fragment: 'section-2'
);
echo "Полный URL: $url\n";
// => /posts/advanced-routing?ref=homepage&utm_source=newsletter#section-2

// Документация с пагинацией и якорем
$url = $router->url(
    name: 'docs.show',
    params: ['section' => 'middleware', 'page' => 'custom'],
    query: ['highlight' => 'true'],
    fragment: 'examples'
);
echo "Документация: $url\n";
// => /docs/middleware/custom?highlight=true#examples

echo "\n=== 5. Опциональные параметры ===\n\n";

// Без опционального параметра
$url = $router->url('blog.index');
echo "blog.index (без категории): $url\n";
// => /blog

// С опциональным параметром
$url = $router->url('blog.index', ['category' => 'php']);
echo "blog.index (с категорией): $url\n";
// => /blog/php

// С опциональным параметром и query
$url = $router->url('blog.index', ['category' => 'php'], ['page' => 3]);
echo "blog.index (полный): $url\n";
// => /blog/php?page=3

// Docs без опционального page
$url = $router->url('docs.show', ['section' => 'getting-started']);
echo "docs.show (без page): $url\n";
// => /docs/getting-started

echo "\n=== 6. Массивы в query параметрах ===\n\n";

// Query с массивами
$url = $router->url('users.index', [], [
    'filters' => ['status' => 'active', 'role' => 'admin'],
    'ids' => [1, 2, 3],
]);
echo "users.index с массивами: $url\n";
// => /users?filters[status]=active&filters[role]=admin&ids[0]=1&ids[1]=2&ids[2]=3

echo "\n=== 7. Специальные символы ===\n\n";

// URL-кодирование специальных символов
$url = $router->url('search', [], [
    'q' => 'php & javascript',
    'tags' => 'web, api, rest',
]);
echo "search со спецсимволами: $url\n";
// => /search?q=php+%26+javascript&tags=web%2C+api%2C+rest

echo "\n=== 8. Проверка существования маршрута ===\n\n";

if ($router->has('users.show')) {
    echo "✅ Маршрут 'users.show' существует\n";
    $url = $router->url('users.show', ['id' => 999]);
    echo "   URL: $url\n";
}

if (!$router->has('admin.dashboard')) {
    echo "❌ Маршрут 'admin.dashboard' не найден\n";
}

echo "\n=== 9. Обработка ошибок ===\n\n";

// Попытка сгенерировать URL для несуществующего маршрута
try {
    $url = $router->url('nonexistent.route');
} catch (InvalidArgumentException $e) {
    echo "❌ Ошибка: {$e->getMessage()}\n";
}

// Попытка сгенерировать URL без обязательного параметра
try {
    $url = $router->url('users.show'); // Нужен параметр 'id'
} catch (InvalidArgumentException $e) {
    echo "❌ Ошибка: {$e->getMessage()}\n";
}

// Попытка использовать неправильный параметр (нарушение constraint)
try {
    $url = $router->url('users.show', ['id' => 'abc']); // Должно быть число
} catch (InvalidArgumentException $e) {
    echo "❌ Ошибка: {$e->getMessage()}\n";
}

echo "\n=== 10. Практические примеры ===\n\n";

// Пагинация
function generatePaginationLinks(Router $router, int $currentPage, int $totalPages): array
{
    $links = [];

    if ($currentPage > 1) {
        $links['prev'] = $router->url('users.index', [], ['page' => $currentPage - 1]);
    }

    $links['current'] = $router->url('users.index', [], ['page' => $currentPage]);

    if ($currentPage < $totalPages) {
        $links['next'] = $router->url('users.index', [], ['page' => $currentPage + 1]);
    }

    return $links;
}

$pagination = generatePaginationLinks($router, 2, 5);
echo "Пагинация (страница 2 из 5):\n";
echo '  Предыдущая: ' . ($pagination['prev'] ?? 'нет') . "\n";
echo "  Текущая: {$pagination['current']}\n";
echo "  Следующая: {$pagination['next']}\n";

echo "\n";

// Навигация по документации
function generateDocsNav(Router $router): array
{
    return [
        'Getting Started' => $router->url('docs.show', ['section' => 'getting-started'], [], 'installation'),
        'Routing' => $router->url('docs.show', ['section' => 'routing', 'page' => 'basic'], [], 'examples'),
        'Middleware' => $router->url('docs.show', ['section' => 'middleware', 'page' => 'creating'], [], 'custom-middleware'),
    ];
}

$docsNav = generateDocsNav($router);
echo "Навигация по документации:\n";
foreach ($docsNav as $title => $url) {
    echo "  $title: $url\n";
}

echo "\n";

// Breadcrumbs с якорями
function generateBreadcrumbs(Router $router, string $section, ?string $page = null): array
{
    $breadcrumbs = [
        'Home' => $router->url('docs.show', ['section' => 'home']),
        ucfirst($section) => $router->url('docs.show', ['section' => $section]),
    ];

    if ($page) {
        $breadcrumbs[ucfirst($page)] = $router->url('docs.show', ['section' => $section, 'page' => $page], [], 'top');
    }

    return $breadcrumbs;
}

$breadcrumbs = generateBreadcrumbs($router, 'api', 'authentication');
echo "Breadcrumbs:\n";
foreach ($breadcrumbs as $title => $url) {
    echo "  $title: $url\n";
}

echo "\n=== Итоги ===\n\n";
echo "✅ URL генерация с path параметрами\n";
echo "✅ Query параметры (?key=value)\n";
echo "✅ Якоря/фрагменты (#section)\n";
echo "✅ Опциональные параметры\n";
echo "✅ Массивы в query параметрах\n";
echo "✅ URL-кодирование специальных символов\n";
echo "✅ Валидация constraints\n";
echo "✅ Обработка ошибок\n";
echo "✅ Практические примеры использования\n";
