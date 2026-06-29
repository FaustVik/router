<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Middleware\AuthMiddleware;
use FaustVik\Router\Middleware\CorsMiddleware;
use FaustVik\Router\Middleware\LoggingMiddleware;
use FaustVik\Router\Router\QuickRouter;

/**
 * Пример использования Middleware с реальной конфигурацией
 *
 * Демонстрирует:
 * - Создание AuthMiddleware с кастомным валидатором токенов
 * - Настройка CorsMiddleware для конкретных доменов
 * - Логирование запросов
 * - Стек middleware для API эндпоинтов
 */

// 1. Создаем валидатор токенов (в реальном приложении это может быть JWT валидатор)
$tokenValidator = function (string $token): ?array {
    // Здесь должна быть ваша логика проверки токена
    // Например: проверка JWT, поиск в базе данных, проверка Redis и т.д.

    // Пример: простая проверка токенов из "базы данных"
    $tokens = [
        'secret-admin-token-2024' => [
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'permissions' => ['read', 'write', 'delete'],
        ],
        'secret-user-token-2024' => [
            'id' => 2,
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'role' => 'user',
            'permissions' => ['read'],
        ],
    ];

    return $tokens[$token] ?? null;
};

// 2. Создаем middleware
$authMiddleware = new AuthMiddleware($tokenValidator);

$corsMiddleware = new CorsMiddleware(
    allowedOrigins: ['https://example.com', 'https://app.example.com'],
    allowedMethods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization', 'X-Api-Key'],
    allowCredentials: true,
    maxAge: 86400 // 24 часа
);

$loggingMiddleware = new LoggingMiddleware(
    logFile: __DIR__ . '/logs/api.log',
    customLogger: null,
    includeUserAgent: true,
    includeIp: true
);

// 3. Создаем роутер
$router = new QuickRouter();

// 4. Публичный эндпоинт (без авторизации)
$router->get('/api/public/status', function (Request $request): Response {
    return Response::json([
        'status' => 'ok',
        'timestamp' => time(),
        'server' => 'FaustVik Router v2.0',
    ]);
});

// 5. Защищенный эндпоинт с middleware
$router->get('/api/protected/profile', function (Request $request): Response {
    // Данные пользователя доступны из атрибутов запроса
    $user = $request->getAttribute('user');
    $isAuthenticated = $request->getAttribute('authenticated');

    return Response::json([
        'authenticated' => $isAuthenticated,
        'user' => $user,
        'message' => 'This is protected data',
    ]);
})->setMiddleware([$authMiddleware]);

// 6. Эндпоинт только для админов
$router->get('/api/admin/users', function (Request $request): Response {
    $user = $request->getAttribute('user');

    // Проверяем роль (это можно вынести в отдельный middleware RoleMiddleware)
    if ($user['role'] !== 'admin') {
        return Response::json([
            'error' => 'Forbidden',
            'message' => 'Admin access required',
        ], 403);
    }

    return Response::json([
        'users' => [
            ['id' => 1, 'name' => 'Admin User'],
            ['id' => 2, 'name' => 'John Doe'],
            ['id' => 3, 'name' => 'Jane Smith'],
        ],
    ]);
})->setMiddleware([$authMiddleware]);

// 7. Эндпоинт с несколькими middleware
$router->post('/api/protected/data', function (Request $request): Response {
    $user = $request->getAttribute('user');
    $data = $request->getBody();

    return Response::json([
        'success' => true,
        'message' => 'Data received',
        'user' => $user['username'],
        'data' => $data,
    ], 201);
})->setMiddleware([$corsMiddleware, $authMiddleware, $loggingMiddleware]);

// 8. Создаем request
$request = Request::createFromGlobals();

try {
    // 9. Обрабатываем запрос
    $response = $router->dispatch($request);

    // 10. Отправляем ответ
    $response->send();
} catch (Exception $e) {
    $errorResponse = Response::json([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
    ], 500);

    $errorResponse->send();
}

/*
 * Примеры запросов для тестирования:
 *
 * 1. Публичный эндпоинт (без токена):
 *    curl http://localhost:8000/api/public/status
 *
 * 2. Защищенный эндпоинт с валидным токеном:
 *    curl -H "Authorization: Bearer secret-user-token-2024" \
 *         http://localhost:8000/api/protected/profile
 *
 * 3. Админский эндпоинт с админским токеном:
 *    curl -H "Authorization: Bearer secret-admin-token-2024" \
 *         http://localhost:8000/api/admin/users
 *
 * 4. Админский эндпоинт с обычным токеном (будет 403):
 *    curl -H "Authorization: Bearer secret-user-token-2024" \
 *         http://localhost:8000/api/admin/users
 *
 * 5. POST запрос с данными:
 *    curl -X POST \
 *         -H "Authorization: Bearer secret-user-token-2024" \
 *         -H "Content-Type: application/json" \
 *         -d '{"name":"Test","value":123}' \
 *         http://localhost:8000/api/protected/data
 *
 * 6. Без токена (будет 401):
 *    curl http://localhost:8000/api/protected/profile
 */
