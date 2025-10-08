<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\interfaces\Middleware\MiddlewareInterface;
use FaustVik\Router\Middleware\CorsMiddleware;
use FaustVik\Router\Middleware\LoggingMiddleware;
use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;
use FaustVik\Router\Router\QuickRouter;

/**
 * Пример использования глобальных middleware
 * 
 * Глобальные middleware применяются ко всем маршрутам автоматически.
 * Это удобно для:
 * - CORS заголовков
 * - Логирования всех запросов
 * - Rate limiting
 * - Аутентификации
 * - И других задач, которые нужны для всех маршрутов
 */

echo "=== Пример 1: Router с глобальными middleware ===\n\n";

// Создаем простой middleware для логирования
class SimpleLoggingMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        echo "[GLOBAL LOG] Запрос: {$request->getMethod()} {$request->getUri()}\n";
        
        $response = $next($request);
        
        echo "[GLOBAL LOG] Ответ: {$response->getStatusCode()}\n\n";
        
        return $response;
    }
}

// Middleware для добавления заголовка
class HeaderMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);
        
        return $response->withHeader('X-Powered-By', 'FaustVik Router v2.0');
    }
}

// Middleware на уровне маршрута
class RouteSpecificMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        echo "[ROUTE MIDDLEWARE] Специфический middleware для маршрута\n";
        return $next($request);
    }
}

$router = new Router();

// Добавляем глобальные middleware (будут применяться ко всем маршрутам)
$router->addGlobalMiddleware(SimpleLoggingMiddleware::class)
       ->addGlobalMiddleware(HeaderMiddleware::class);

// Создаем маршруты
$routes = new RoutesCollection();

// Маршрут без своих middleware - использует только глобальные
$routes->set(
    \FaustVik\Router\Route\RouteAnonymousFunc::create(
        route: '/',
        func: function() {
            return "Hello World!";
        },
        methods: ['GET']
    )
);

// Маршрут со своими middleware - использует глобальные + свои
$routes->set(
    \FaustVik\Router\Route\RouteAnonymousFunc::create(
        route: '/protected',
        func: function() {
            return "Protected resource";
        },
        methods: ['GET']
    )->middleware([RouteSpecificMiddleware::class])
);

$router->setCollection($routes);

// Симуляция запроса к первому маршруту
echo "--- Запрос к / (только глобальные middleware) ---\n";
$router->setUri('/');
try {
    ob_start();
    $router->run();
    $output = ob_get_clean();
    echo "Результат: $output\n\n";
} catch (\Throwable $e) {
    ob_end_clean();
    echo "Ошибка: {$e->getMessage()}\n\n";
}

// Симуляция запроса ко второму маршруту
echo "--- Запрос к /protected (глобальные + специфичные middleware) ---\n";
$router->setUri('/protected');
try {
    ob_start();
    $router->run();
    $output = ob_get_clean();
    echo "Результат: $output\n\n";
} catch (\Throwable $e) {
    ob_end_clean();
    echo "Ошибка: {$e->getMessage()}\n\n";
}

echo "\n=== Пример 2: QuickRouter с глобальными middleware ===\n\n";

$app = new QuickRouter();

// Добавляем глобальные middleware в QuickRouter
$app->addMiddleware(SimpleLoggingMiddleware::class)
    ->addMiddleware(HeaderMiddleware::class);

// Простые маршруты
$app->get('/', fn() => "QuickRouter Home");
$app->get('/api/users', fn() => "Users API");

// Маршрут с собственным middleware
$app->get('/admin', fn() => "Admin Panel")
    ->middleware([RouteSpecificMiddleware::class]);

echo "--- Запрос к / ---\n";
$app->advanced()->setUri('/');
try {
    ob_start();
    $app->run();
    $output = ob_get_clean();
    echo "Результат: $output\n\n";
} catch (\Throwable $e) {
    ob_end_clean();
    echo "Ошибка: {$e->getMessage()}\n\n";
}

echo "--- Запрос к /api/users ---\n";
$app->advanced()->setUri('/api/users');
try {
    ob_start();
    $app->run();
    $output = ob_get_clean();
    echo "Результат: $output\n\n";
} catch (\Throwable $e) {
    ob_end_clean();
    echo "Ошибка: {$e->getMessage()}\n\n";
}

echo "--- Запрос к /admin (с дополнительным middleware) ---\n";
$app->advanced()->setUri('/admin');
try {
    ob_start();
    $app->run();
    $output = ob_get_clean();
    echo "Результат: $output\n\n";
} catch (\Throwable $e) {
    ob_end_clean();
    echo "Ошибка: {$e->getMessage()}\n\n";
}

echo "\n=== Пример 3: Управление глобальными middleware ===\n\n";

$router2 = new Router();

// Добавляем несколько middleware
$router2->addGlobalMiddleware(SimpleLoggingMiddleware::class)
        ->addGlobalMiddleware(HeaderMiddleware::class);

echo "Глобальных middleware: " . count($router2->getGlobalMiddleware()) . "\n";

// Можно заменить все middleware сразу
$router2->setGlobalMiddleware([
    SimpleLoggingMiddleware::class,
]);

echo "После замены: " . count($router2->getGlobalMiddleware()) . "\n";

// Или очистить все
$router2->clearGlobalMiddleware();
echo "После очистки: " . count($router2->getGlobalMiddleware()) . "\n\n";

echo "\n=== Пример 4: Использование с реальными middleware (CORS, Logging) ===\n\n";

$app2 = new QuickRouter();

// Добавляем CORS и Logging middleware глобально
$app2->addMiddleware(new CorsMiddleware(
    allowedOrigins: ['http://localhost:3000'],
    allowedMethods: ['GET', 'POST', 'PUT', 'DELETE'],
    allowedHeaders: ['Content-Type', 'Authorization']
));

$app2->addMiddleware(new LoggingMiddleware());

$app2->get('/api/data', fn() => json_encode(['data' => 'Some data']));

echo "--- API запрос с CORS и логированием ---\n";
$app2->advanced()->setUri('/api/data');
try {
    ob_start();
    $app2->run();
    $output = ob_get_clean();
    echo "Результат: $output\n\n";
} catch (\Throwable $e) {
    ob_end_clean();
    echo "Ошибка: {$e->getMessage()}\n\n";
}

echo "\n=== Итоги ===\n\n";
echo "✅ Глобальные middleware добавлены в Router и QuickRouter\n";
echo "✅ Они применяются ко всем маршрутам автоматически\n";
echo "✅ Middleware маршрута выполняются после глобальных\n";
echo "✅ Можно добавлять, заменять и очищать глобальные middleware\n";
echo "✅ Работает с DI контейнером (если middleware требуют зависимости)\n";

