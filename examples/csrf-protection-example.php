<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Middleware\CsrfMiddleware;
use FaustVik\Router\Router\QuickRouter;

/**
 * Пример использования CsrfMiddleware для защиты от CSRF атак
 *
 * Middleware защищает от межсайтовой подделки запросов:
 * - Автоматически генерирует CSRF токены
 * - Проверяет токены для POST/PUT/PATCH/DELETE запросов
 * - Поддерживает токены в формах, заголовках и query параметрах
 * - Возвращает 419 Page Expired при несовпадении токена
 */

// Создаем роутер
$router = QuickRouter::create();

// ====================================================================
// Пример 1: Базовая защита CSRF для всего приложения
// ====================================================================

$csrfMiddleware = new CsrfMiddleware(
    tokenLength: 32,                    // длина токена в байтах (32 байта = 64 hex символа)
    sessionKey: '_csrf_token',          // ключ в сессии
    excludePaths: ['/api/webhook']      // пути без CSRF проверки (webhook endpoints)
);

// Применяем глобально ко всем маршрутам
$router->addGlobalMiddleware($csrfMiddleware);

// ====================================================================
// Пример 2: Страница с HTML формой
// ====================================================================

$router->get('/', function () {
    $csrfTokenField = CsrfMiddleware::getTokenField();
    $csrfTokenMeta = CsrfMiddleware::getTokenMeta();

    return new Response(<<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSRF Protection Example</title>
    {$csrfTokenMeta}
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        .info { background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🛡️ CSRF Protection Demo</h1>
    
    <div class="info">
        <strong>Информация:</strong> Все формы автоматически защищены от CSRF атак.
        Токен: <code>{$_SESSION['_csrf_token']}</code>
    </div>

    <h2>Форма 1: Создание поста (с скрытым полем)</h2>
    <form method="POST" action="/posts">
        {$csrfTokenField}
        <div class="form-group">
            <label>Заголовок:</label>
            <input type="text" name="title" required>
        </div>
        <div class="form-group">
            <label>Содержание:</label>
            <textarea name="content" rows="5" required></textarea>
        </div>
        <button type="submit">Создать пост</button>
    </form>

    <h2>Форма 2: Вход в систему</h2>
    <form method="POST" action="/auth/login">
        {$csrfTokenField}
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="user@example.com" required>
        </div>
        <div class="form-group">
            <label>Пароль:</label>
            <input type="password" name="password" value="password123" required>
        </div>
        <button type="submit">Войти</button>
    </form>

    <h2>AJAX запрос с CSRF токеном</h2>
    <button onclick="makeAjaxRequest()">Отправить AJAX запрос</button>
    <div id="ajax-result" style="margin-top: 10px;"></div>

    <script>
        function makeAjaxRequest() {
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const resultDiv = document.getElementById('ajax-result');
            
            fetch('/api/profile/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': token  // Токен в заголовке
                },
                body: JSON.stringify({
                    name: 'John Doe',
                    email: 'john@example.com'
                })
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.innerHTML = '<div class="info">✅ ' + data.message + '</div>';
            })
            .catch(error => {
                resultDiv.innerHTML = '<div style="color: red;">❌ Ошибка: ' + error + '</div>';
            });
        }
    </script>

    <h2>Тест без CSRF токена (будет ошибка)</h2>
    <form method="POST" action="/test/no-csrf">
        <div class="form-group">
            <label>Данные:</label>
            <input type="text" name="data" value="test data">
        </div>
        <button type="submit">Отправить БЕЗ CSRF токена</button>
    </form>
</body>
</html>
HTML
    );
});

// ====================================================================
// Пример 3: Обработка форм с CSRF защитой
// ====================================================================

$router->post('/posts', function (Request $request) {
    $title = $request->input('title');
    $content = $request->input('content');

    // Здесь бы сохранили в БД
    // $postRepository->create(['title' => $title, 'content' => $content]);

    return Response::json([
        'success' => true,
        'message' => 'Пост успешно создан!',
        'data' => [
            'id' => rand(1, 1000),
            'title' => $title,
            'content' => $content
        ]
    ], 201);
});

$router->post('/auth/login', function (Request $request) {
    $email = $request->input('email');
    $password = $request->input('password');

    // Здесь бы проверили credentials
    // if ($auth->attempt($email, $password)) { ... }

    return Response::json([
        'success' => true,
        'message' => 'Успешный вход в систему',
        'user' => [
            'email' => $email,
            'name' => 'John Doe'
        ]
    ]);
});

// ====================================================================
// Пример 4: AJAX API endpoint с CSRF защитой
// ====================================================================

$router->post('/api/profile/update', function (Request $request) {
    $body = $request->getBody();

    return Response::json([
        'success' => true,
        'message' => 'Профиль успешно обновлен!',
        'data' => $body
    ]);
});

// ====================================================================
// Пример 5: Webhook без CSRF проверки
// ====================================================================

// Этот endpoint исключен из CSRF проверки (см. excludePaths выше)
$router->post('/api/webhook', function (Request $request) {
    // Webhook от внешнего сервиса (GitHub, Stripe и т.д.)
    // Здесь используется другая аутентификация (подпись, secret key)

    return Response::json([
        'success' => true,
        'message' => 'Webhook received'
    ]);
});

// ====================================================================
// Пример 6: Тест без CSRF токена (вернет 419)
// ====================================================================

$router->post('/test/no-csrf', function () {
    // Этот код не выполнится, т.к. middleware заблокирует запрос
    return Response::json([
        'success' => true,
        'message' => 'This should never execute'
    ]);
});

// ====================================================================
// Пример 7: Регенерация токена после важных действий
// ====================================================================

$router->post('/auth/logout', function () use ($csrfMiddleware) {
    // После logout регенерируем CSRF токен для безопасности
    $newToken = $csrfMiddleware->regenerateToken();

    return Response::json([
        'success' => true,
        'message' => 'Выход выполнен успешно',
        'new_csrf_token' => $newToken
    ]);
});

// ====================================================================
// Пример 8: REST API группа с отдельным CSRF middleware
// ====================================================================

$apiCsrfMiddleware = new CsrfMiddleware(
    tokenLength: 32,
    sessionKey: '_api_csrf_token',
    excludePaths: []  // для API нет исключений
);

$router->group('/api/admin', function ($group) {
    $group->post('/users', function () {
        return Response::json(['message' => 'User created']);
    });

    $group->delete('/users/{id}', function ($id) {
        return Response::json(['message' => "User {$id} deleted"]);
    });

    $group->put('/users/{id}', function ($id) {
        return Response::json(['message' => "User {$id} updated"]);
    });
})->middleware($apiCsrfMiddleware);

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
 *    php -S localhost:8000 -t examples examples/csrf-protection-example.php
 *
 * 2. Откройте браузер:
 *    http://localhost:8000
 *
 * 3. Протестируйте формы:
 *    - Форма с CSRF токеном → успех (200)
 *    - Форма без токена → ошибка (419)
 *    - AJAX с токеном в заголовке → успех
 *
 * 4. Тест через curl с токеном:
 *    # Получаем токен
 *    TOKEN=$(curl -c cookies.txt http://localhost:8000 | grep -oP '(?<=value=")[^"]+')
 *
 *    # Отправляем с токеном
 *    curl -b cookies.txt -X POST http://localhost:8000/posts \
 *         -d "title=Test&content=Content&_csrf_token=$TOKEN"
 *
 * 5. Тест без токена (получим 419):
 *    curl -X POST http://localhost:8000/posts \
 *         -d "title=Test&content=Content"
 *
 * ====================================================================
 * Production рекомендации:
 * ====================================================================
 *
 * 1. Регенерируйте токен после login/logout
 * 2. Используйте HTTPS для защиты токена в transit
 * 3. Исключайте только необходимые пути (webhook endpoints)
 * 4. Для SPA используйте токен в заголовке X-CSRF-Token
 * 5. Логируйте неудачные проверки CSRF для мониторинга атак
 * 6. Рассмотрите SameSite cookie флаг для дополнительной защиты
 */
