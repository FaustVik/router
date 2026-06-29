<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Cache\FileCache;
use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Interfaces\Cache\CacheInterface;
use FaustVik\Router\Interfaces\Middleware\MiddlewareInterface;
use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;

/**
 * Пример 1: Простой middleware БЕЗ зависимостей
 * Работает "из коробки" - не требует DI контейнера
 */
class SimpleLoggingMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        echo "🚀 [LOG] Запрос: {$request->getMethod()} {$request->getUri()}\n";

        $response = $next($request);

        echo "✅ [LOG] Ответ: {$response->getStatusCode()}\n";

        return $response;
    }
}

/**
 * Пример 2: Middleware С зависимостями
 * Требует регистрации в DI контейнере
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CacheInterface $cache,
        private int $maxAttempts = 10
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $key = 'rate_limit:' . $request->getClientIp();
        $attempts = (int) $this->cache->get($key);

        if ($attempts >= $this->maxAttempts) {
            echo "❌ [RATE LIMIT] Превышен лимит запросов!\n";
            return Response::json([
                'error' => 'Too many requests',
                'retry_after' => 60,
            ], 429);
        }

        $this->cache->set($key, $attempts + 1, 60);
        echo "📊 [RATE LIMIT] Попытка {$attempts}/{$this->maxAttempts}\n";

        return $next($request);
    }
}

/**
 * Пример 3: Middleware с логикой аутентификации и зависимостями
 */
class DatabaseAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CacheInterface $cache,
        private string $tokenHeader = 'Authorization'
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $token = $request->getHeader($this->tokenHeader);

        if (!$token) {
            echo "🔒 [AUTH] Токен не предоставлен\n";
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        // Проверяем кеш токенов
        $userId = $this->cache->get('token:' . $token);

        if (!$userId) {
            echo "🔒 [AUTH] Невалидный токен\n";
            return Response::json(['error' => 'Invalid token'], 401);
        }

        echo "✅ [AUTH] Пользователь #{$userId} аутентифицирован\n";

        // Добавляем информацию о пользователе в запрос
        $request = $request->withAttribute('user_id', $userId);

        return $next($request);
    }
}

// ============================================================================
// НАСТРОЙКА РОУТЕРА
// ============================================================================

echo '=' . str_repeat('=', 70) . "\n";
echo "  Демонстрация DI в MiddlewareStack\n";
echo '=' . str_repeat('=', 70) . "\n\n";

$router = new Router();

// Получаем DI контейнер роутера
$container = $router->getContainer();

// ============================================================================
// РЕГИСТРАЦИЯ ЗАВИСИМОСТЕЙ В КОНТЕЙНЕРЕ
// ============================================================================

// Регистрируем кеш как singleton
$cache = new FileCache(__DIR__ . '/cache', 'middleware_di_');
$container->singleton(CacheInterface::class, $cache);

// Регистрируем middleware с зависимостями
$container->bind(RateLimitMiddleware::class, function ($c) {
    return new RateLimitMiddleware(
        cache: $c->resolve(CacheInterface::class),
        maxAttempts: 5  // Максимум 5 запросов
    );
});

$container->bind(DatabaseAuthMiddleware::class, function ($c) {
    return new DatabaseAuthMiddleware(
        cache: $c->resolve(CacheInterface::class),
        tokenHeader: 'X-Auth-Token'
    );
});

echo "✅ Зависимости зарегистрированы в DI контейнере\n\n";

// ============================================================================
// СОЗДАНИЕ МАРШРУТОВ
// ============================================================================

// Маршрут 1: Только простой middleware (без DI)
$publicRoute = Route::create('/public', function (Request $request) {
    return Response::json([
        'message' => 'Публичный эндпоинт',
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
})->middleware([
    SimpleLoggingMiddleware::class,  // ✅ Работает без DI - создается напрямую
]);

// Маршрут 2: Middleware с зависимостями (требует DI)
$limitedRoute = Route::create('/api/limited', function (Request $request) {
    return Response::json([
        'message' => 'Эндпоинт с rate limiting',
        'data' => ['value' => 123],
    ]);
})->middleware([
    SimpleLoggingMiddleware::class,  // ✅ Без DI
    RateLimitMiddleware::class,       // ✅ Через DI - есть зависимости
]);

// Маршрут 3: Защищенный эндпоинт (аутентификация + rate limit)
$protectedRoute = Route::create('/api/protected', function (Request $request) {
    $userId = $request->getAttribute('user_id');

    return Response::json([
        'message' => 'Защищенный эндпоинт',
        'user_id' => $userId,
        'data' => ['secret' => 'very important data'],
    ]);
})->middleware([
    SimpleLoggingMiddleware::class,   // ✅ Без DI
    DatabaseAuthMiddleware::class,    // ✅ Через DI
    RateLimitMiddleware::class,        // ✅ Через DI
]);

// Регистрируем маршруты
$routes = new RoutesCollection();
$routes->add($publicRoute);
$routes->add($limitedRoute);
$routes->add($protectedRoute);

$router->setCollection($routes);

// ============================================================================
// ТЕСТИРОВАНИЕ
// ============================================================================

echo str_repeat('-', 72) . "\n";
echo "ТЕСТ 1: Публичный маршрут (только SimpleLoggingMiddleware)\n";
echo str_repeat('-', 72) . "\n";

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/public';

try {
    $router->run();
} catch (Throwable $e) {
    echo '❌ Ошибка: ' . $e->getMessage() . "\n";
}

echo "\n\n";

echo str_repeat('-', 72) . "\n";
echo "ТЕСТ 2: Маршрут с rate limiting (5 попыток)\n";
echo str_repeat('-', 72) . "\n";

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

for ($i = 1; $i <= 7; $i++) {
    echo "\n--- Попытка #{$i} ---\n";
    $_SERVER['REQUEST_URI'] = '/api/limited';

    try {
        $router->run();
    } catch (Throwable $e) {
        echo '❌ Ошибка: ' . $e->getMessage() . "\n";
    }

    // Небольшая пауза для визуализации
    usleep(100000);
}

echo "\n\n";

echo str_repeat('-', 72) . "\n";
echo "ТЕСТ 3: Защищенный маршрут (требует токен)\n";
echo str_repeat('-', 72) . "\n";

// Симулируем создание токена в кеше
$cache->set('token:valid_token_123', 'user_42', 3600);

echo "\n--- Попытка без токена ---\n";
$_SERVER['REQUEST_URI'] = '/api/protected';
unset($_SERVER['HTTP_X_AUTH_TOKEN']);

try {
    $router->run();
} catch (Throwable $e) {
    echo '❌ Ошибка: ' . $e->getMessage() . "\n";
}

echo "\n--- Попытка с валидным токеном ---\n";
$_SERVER['HTTP_X_AUTH_TOKEN'] = 'valid_token_123';

try {
    $router->run();
} catch (Throwable $e) {
    echo '❌ Ошибка: ' . $e->getMessage() . "\n";
}

echo "\n\n";

// ============================================================================
// ДЕМОНСТРАЦИЯ ОШИБКИ БЕЗ DI
// ============================================================================

echo str_repeat('=', 72) . "\n";
echo "ДЕМОНСТРАЦИЯ: Что будет если middleware с зависимостями, но нет DI?\n";
echo str_repeat('=', 72) . "\n\n";

class MiddlewareRequiringDependencies implements MiddlewareInterface
{
    public function __construct(
        private CacheInterface $cache  // Требует зависимость!
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        return $next($request);
    }
}

$problematicRoute = Route::create('/test-no-di', function () {
    return Response::json(['message' => 'ok']);
})->middleware([
    MiddlewareRequiringDependencies::class,  // ❌ Не зарегистрирован в DI!
]);

$testRouter = new Router();
$testCollection = new RoutesCollection();
$testCollection->add($problematicRoute);
$testRouter->setCollection($testCollection);

$_SERVER['REQUEST_URI'] = '/test-no-di';

try {
    $testRouter->run();
} catch (RuntimeException $e) {
    echo "✅ Получили понятную ошибку:\n";
    echo '   ' . $e->getMessage() . "\n\n";
}

echo str_repeat('=', 72) . "\n";
echo "✅ Демонстрация завершена!\n";
echo str_repeat('=', 72) . "\n";
