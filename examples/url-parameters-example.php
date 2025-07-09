<?php

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Http\Request;
use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RouteAnonymousFunc;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;

// Контроллеры для демонстрации параметров
class BlogController
{
    public function showPost($id)
    {
        echo "=== Blog Post ===\n";
        echo "Post ID: $id\n";
        echo "Title: Sample Post #$id\n";
        echo "Content: This is the content of post $id...\n";
    }

    public function showCategoryPost($category, $id)
    {
        echo "=== Category Post ===\n";
        echo "Category: $category\n";
        echo "Post ID: $id\n";
        echo "Title: Post #$id in $category category\n";
        echo "URL pattern: /blog/{category}/post/{id}\n";
    }
}

class UserController
{
    public function show(Request $request, $id)
    {
        echo "=== User Profile (with Request) ===\n";
        echo "User ID: $id\n";
        echo "Request Method: " . $request->getMethod() . "\n";
        echo "Request URI: " . $request->getUri() . "\n";

        // Демонстрируем приоритет параметров URL над query параметрами
        $queryId = $_GET['id'] ?? 'none';
        echo "Query parameter 'id': $queryId\n";
        echo "URL parameter 'id': $id (has priority)\n";
    }

    public function showPosts($userId, $postId)
    {
        echo "=== User Posts ===\n";
        echo "User ID: $userId\n";
        echo "Post ID: $postId\n";
        echo "URL pattern: /user/{userId}/posts/{postId}\n";
    }

    public function edit($id)
    {
        echo "=== Edit User ===\n";
        echo "Editing user ID: $id\n";
        echo "URL pattern: /user/{id}/edit\n";
    }

    public function editSection($id, $section)
    {
        echo "=== Edit User Section ===\n";
        echo "User ID: $id\n";
        echo "Section: $section\n";
        echo "URL pattern: /user/{id}/edit/{section}\n";
    }
}

// Создаем коллекцию маршрутов
$routes = new RoutesCollection();

// === Одиночные параметры ===
$routes->set(Route::create('/blog/{id}', BlogController::class, 'showPost', [], ['GET']));

// === Множественные параметры ===
$routes->set(Route::create('/blog/{category}/post/{id}', BlogController::class, 'showCategoryPost', [], ['GET']));

// === Параметры с Request injection ===
$routes->set(Route::create('/user/{id}', UserController::class, 'show', [], ['GET']));

// === Три параметра ===
$routes->set(Route::create('/user/{userId}/posts/{postId}', UserController::class, 'showPosts', [], ['GET']));

// === "Опциональные" параметры (через разные маршруты) ===
$routes->set(Route::create('/user/{id}/edit', UserController::class, 'edit', [], ['GET']));
$routes->set(Route::create('/user/{id}/edit/{section}', UserController::class, 'editSection', [], ['GET']));

// === Анонимные функции с параметрами ===
$routes->set(RouteAnonymousFunc::create('/product/{id}', static function ($id) {
    echo "=== Product Details ===\n";
    echo "Product ID: $id\n";
    echo "Name: Product #$id\n";
    echo "Price: $" . ($id * 10) . ".99\n";
    echo "This route uses an anonymous function.\n";
}, ['GET']));

// === Глубоко вложенные параметры ===
$routes->set(RouteAnonymousFunc::create('/shop/{category}/{subcategory}/{product}', static function ($category, $subcategory, $product) {
    echo "=== Shop Product ===\n";
    echo "Category: $category\n";
    echo "Subcategory: $subcategory\n";
    echo "Product: $product\n";
    echo "URL pattern: /shop/{category}/{subcategory}/{product}\n";
    echo "Example: /shop/electronics/phones/iphone\n";
}, ['GET']));

// === Демонстрация приоритета параметров ===
$routes->set(RouteAnonymousFunc::create('/priority/{id}', static function ($id) {
    $queryId = $_GET['id'] ?? 'none';
    echo "=== Parameter Priority Demo ===\n";
    echo "URL parameter 'id': $id\n";
    echo "Query parameter 'id': $queryId\n";
    echo "URL parameters always have priority over query parameters!\n";
    echo "Try: /priority/123?id=456\n";
}, ['GET']));

// === Валидация параметров ===
$routes->set(RouteAnonymousFunc::create('/validate/{type}/{value}', static function ($type, $value) {
    echo "=== Parameter Validation Demo ===\n";
    echo "Type: $type\n";
    echo "Value: $value\n";

    if ($type === 'number') {
        if (is_numeric($value)) {
            echo "✅ Valid number: " . (int) $value . "\n";
        } else {
            echo "❌ Invalid number format\n";
        }
    } elseif ($type === 'email') {
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            echo "✅ Valid email: $value\n";
        } else {
            echo "❌ Invalid email format\n";
        }
    } else {
        echo "ℹ️  Unknown validation type\n";
    }

    echo "Examples:\n";
    echo "- /validate/number/123\n";
    echo "- /validate/email/test@example.com\n";
}, ['GET']));

// Создаем роутер и запускаем
$router = new Router();

try {
    echo "=== URL Parameters Example ===\n\n";

    echo "Available routes with parameters:\n\n";

    echo "1. Single parameter:\n";
    echo "   /blog/{id} → /blog/123\n\n";

    echo "2. Multiple parameters:\n";
    echo "   /blog/{category}/post/{id} → /blog/tech/post/456\n\n";

    echo "3. User routes with Request injection:\n";
    echo "   /user/{id} → /user/789\n\n";

    echo "4. Three parameters:\n";
    echo "   /user/{userId}/posts/{postId} → /user/100/posts/200\n\n";

    echo "5. Optional-like parameters:\n";
    echo "   /user/{id}/edit → /user/123/edit\n";
    echo "   /user/{id}/edit/{section} → /user/123/edit/password\n\n";

    echo "6. Product with anonymous function:\n";
    echo "   /product/{id} → /product/555\n\n";

    echo "7. Deep nested parameters:\n";
    echo "   /shop/{category}/{subcategory}/{product} → /shop/electronics/phones/iphone\n\n";

    echo "8. Parameter priority demo:\n";
    echo "   /priority/{id} → /priority/123?id=456\n\n";

    echo "9. Parameter validation demo:\n";
    echo "   /validate/{type}/{value} → /validate/number/123\n";
    echo "   /validate/{type}/{value} → /validate/email/test@example.com\n\n";

    echo "Test commands:\n";
    echo "REQUEST_URI=\"/blog/123\" php url-parameters-example.php\n";
    echo "REQUEST_URI=\"/blog/tech/post/456\" php url-parameters-example.php\n";
    echo "REQUEST_URI=\"/user/789\" php url-parameters-example.php\n";
    echo "REQUEST_URI=\"/shop/electronics/phones/iphone\" php url-parameters-example.php\n";
    echo "REQUEST_URI=\"/priority/123?id=456\" php url-parameters-example.php\n\n";

    echo "============================================================\n\n";

    $router->setCollection($routes)->run();

    echo "\n\n=== URL Parameters Example Complete ===\n";
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "This route might not exist. Try one of the listed routes above.\n";
}
