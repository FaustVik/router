<?php

require_once __DIR__ . '/vendor/autoload.php';

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Middleware\AuthMiddleware;
use FaustVik\Router\Middleware\LoggingMiddleware;
use FaustVik\Router\Middleware\CorsMiddleware;
use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RouteAnonymousFunc;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;

// Пример контроллера
class UserController 
{
    public function index(Request $request)
    {
        $userId = $request->getAttribute('user_id', 'Guest');
        $isAuthenticated = $request->getAttribute('authenticated', false);
        
        echo "User ID: " . $userId . "\n";
        echo "Authenticated: " . ($isAuthenticated ? 'Yes' : 'No') . "\n";
        echo "Users list here...";
    }
    
    public function show(Request $request, $id)
    {
        $userId = $request->getAttribute('user_id', 'Guest');
        echo "Showing user $id (requested by user $userId)";
    }
}

// Создаем коллекцию маршрутов
$collections = new RoutesCollection();

// Обычный маршрут
$collections->set(
    Route::create('/', UserController::class, 'index', [], ['GET'])
);

// Маршрут с middleware
$collections->set(
    Route::create('/users', UserController::class, 'index', [], ['GET'])
        ->middleware([
            LoggingMiddleware::class,
            CorsMiddleware::class
        ])
);

// Защищенный маршрут с авторизацией
$collections->set(
    Route::create('/admin/users', UserController::class, 'index', [], ['GET'])
        ->middleware([
            AuthMiddleware::class,
            LoggingMiddleware::class
        ])
);

// Маршрут с параметрами и middleware
$collections->set(
    Route::create('/users/{id}', UserController::class, 'show', [], ['GET'])
        ->middleware([
            LoggingMiddleware::class
        ])
);

// API маршрут с анонимной функцией
$collections->set(
    RouteAnonymousFunc::create('/api/status', function(Request $request) {
        return Response::json([
            'status' => 'ok',
            'timestamp' => time(),
            'method' => $request->getMethod(),
            'uri' => $request->getUri()
        ]);
    }, ['GET'])
        ->middleware([
            CorsMiddleware::class,
            LoggingMiddleware::class
        ])
);

// Создаем роутер
$router = new Router();
$router->setCollection($collections);

// Можно установить URI вручную для тестирования
// $router->setUri('/users/123');

// Запускаем роутер
$router->run();

echo "\n\n=== Middleware Example Complete ===\n";
echo "Check router.log for logged requests\n";
echo "\nTry these URLs:\n";
echo "- / (no middleware)\n";
echo "- /users (with logging and CORS)\n";
echo "- /admin/users (with auth and logging - needs Authorization header)\n";
echo "- /users/123 (with logging)\n";
echo "- /api/status (JSON response with CORS and logging)\n"; 