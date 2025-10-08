<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Cache\FileCache;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Middleware\RateLimitMiddleware;
use FaustVik\Router\Router\QuickRouter;

/**
 * Пример использования RateLimitMiddleware для защиты от DDoS
 *
 * Middleware ограничивает количество запросов с одного IP адреса:
 * - Добавляет заголовки X-RateLimit-*
 * - Возвращает 429 Too Many Requests при превышении лимита
 * - Работает на основе кеша (FileCache, Redis и т.д.)
 */

// Создаем кеш для хранения счетчиков
$cache = new FileCache(
    cacheDir: __DIR__ . '/cache/rate_limit',
    prefix: 'rate_limit_'
);

// Создаем роутер
$router = QuickRouter::create();

// ====================================================================
// Пример 1: Базовое использование - общий лимит для всего API
// ====================================================================

$basicRateLimit = new RateLimitMiddleware(
    cache: $cache,
    maxAttempts: 60,        // 60 запросов
    decaySeconds: 60,       // в минуту (60 секунд)
    includePathInKey: false // единый лимит для всех путей
);

// Применяем глобально ко всем маршрутам
$router->addGlobalMiddleware($basicRateLimit);

// ====================================================================
// Пример 2: Строгий лимит для конкретного endpoint (авторизация)
// ====================================================================

$strictRateLimit = new RateLimitMiddleware(
    cache: $cache,
    maxAttempts: 5,         // только 5 попыток
    decaySeconds: 300,      // за 5 минут (защита от brute-force)
    includePathInKey: true  // отдельный лимит для каждого пути
);

$router->post('/api/auth/login', function () {
    return Response::json([
        'success' => true,
        'message' => 'Login successful',
        'token' => 'example-jwt-token'
    ]);
})->middleware($strictRateLimit);

// ====================================================================
// Пример 3: Разные лимиты для разных endpoint'ов
// ====================================================================

// Лимит для публичного API (более мягкий)
$publicRateLimit = new RateLimitMiddleware(
    cache: $cache,
    maxAttempts: 100,       // 100 запросов
    decaySeconds: 60,       // в минуту
    includePathInKey: true
);

$router->get('/api/public/posts', function () {
    return Response::json([
        'posts' => [
            ['id' => 1, 'title' => 'First Post'],
            ['id' => 2, 'title' => 'Second Post']
        ]
    ]);
})->middleware($publicRateLimit);

// Строгий лимит для операций записи
$writeRateLimit = new RateLimitMiddleware(
    cache: $cache,
    maxAttempts: 30,        // 30 запросов
    decaySeconds: 60,       // в минуту
    includePathInKey: true
);

$router->post('/api/posts', function () {
    return Response::json([
        'success' => true,
        'message' => 'Post created',
        'id' => 123
    ], 201);
})->middleware($writeRateLimit);

// ====================================================================
// Пример 4: Проверка статуса лимита (для отладки)
// ====================================================================

$monitorRateLimit = new RateLimitMiddleware(
    cache: $cache,
    maxAttempts: 10,
    decaySeconds: 60
);

$router->get('/api/rate-limit-status', function () use ($monitorRateLimit) {
    $request = \FaustVik\Router\Http\Request::createFromGlobals();
    $status = $monitorRateLimit->getLimitStatus($request);

    return Response::json([
        'limit' => 10,
        'attempts' => $status['attempts'],
        'remaining' => $status['remaining'],
        'reset_time' => date('Y-m-d H:i:s', $status['reset_time']),
        'reset_in_seconds' => max(0, $status['reset_time'] - time())
    ]);
});

// ====================================================================
// Пример 5: Очистка лимитов (для администраторов)
// ====================================================================

$adminRateLimit = new RateLimitMiddleware(
    cache: $cache,
    maxAttempts: 1000,
    decaySeconds: 60
);

$router->post('/api/admin/clear-rate-limits', function () use ($cache) {
    $cache->clear();

    return Response::json([
        'success' => true,
        'message' => 'All rate limits have been cleared'
    ]);
})->middleware($adminRateLimit);

// ====================================================================
// Тестовый endpoint для проверки rate limiting
// ====================================================================

$testRateLimit = new RateLimitMiddleware(
    cache: $cache,
    maxAttempts: 3,         // всего 3 запроса
    decaySeconds: 60,       // в минуту (для быстрого тестирования)
    includePathInKey: false
);

$router->get('/api/test/rate-limit', function () {
    return Response::json([
        'message' => 'Request successful! Check X-RateLimit-* headers.',
        'tip' => 'Make 4+ requests within a minute to see 429 response'
    ]);
})->middleware($testRateLimit);

// ====================================================================
// Запуск роутера
// ====================================================================

try {
    $router->run();
} catch (\Throwable $e) {
    Response::json([
        'error' => 'Server Error',
        'message' => $e->getMessage()
    ], 500)->send();
}

/*
 * ====================================================================
 * Как протестировать:
 * ====================================================================
 *
 * 1. Запустите встроенный PHP сервер:
 *    php -S localhost:8000 -t examples examples/rate-limit-example.php
 *
 * 2. Тестирование базового лимита (3 запроса в минуту):
 *    curl -v http://localhost:8000/api/test/rate-limit
 *
 *    # Делаем 3 запроса - все должны пройти
 *    # 4-й запрос вернет 429 Too Many Requests
 *
 * 3. Проверка заголовков:
 *    X-RateLimit-Limit: 3
 *    X-RateLimit-Remaining: 2
 *    X-RateLimit-Reset: 1696789200
 *    Retry-After: 45  (только при 429)
 *
 * 4. Проверка статуса:
 *    curl http://localhost:8000/api/rate-limit-status
 *
 * 5. Очистка лимитов (если нужно сбросить для тестов):
 *    curl -X POST http://localhost:8000/api/admin/clear-rate-limits
 *
 * ====================================================================
 * Production рекомендации:
 * ====================================================================
 *
 * 1. Используйте Redis вместо FileCache для лучшей производительности
 * 2. Настройте разные лимиты для авторизованных/неавторизованных
 * 3. Добавьте whitelist IP адресов для администраторов
 * 4. Логируйте превышения лимита для мониторинга атак
 * 5. Используйте includePathInKey: true для API с разными endpoint'ами
 */
