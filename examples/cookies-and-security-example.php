<?php

declare(strict_types=1);

/**
 * Пример работы с Cookies и Security функциями
 *
 * Демонстрирует:
 * - Работу с HTTP Cookies (чтение и установка)
 * - Определение IP адреса клиента
 * - Проверку HTTPS соединения
 * - HTTP Method Override для REST API через формы
 * - Класс Cookie для продвинутой настройки
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Http\Cookie;
use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Router\Router;

$router = new Router();

// ============================================
// 1. Работа с Cookies - простой способ
// ============================================

$router->get('/cookie/set', function (Request $request) {
    $response = Response::json(['message' => 'Cookie установлена']);

    // Простая установка (session cookie)
    $response->setCookie('simple_cookie', 'test_value');

    // С временем жизни (3600 секунд = 1 час)
    $response->setCookie('timed_cookie', 'expires_in_1h', 3600);

    return $response;
});

$router->get('/cookie/get', function (Request $request) {
    // Чтение cookie
    $simpleCookie = $request->getCookie('simple_cookie', 'not_found');
    $timedCookie = $request->getCookie('timed_cookie', 'not_found');

    // Получение всех cookies
    $allCookies = $request->getCookies();

    // Проверка наличия
    $hasSimple = $request->hasCookie('simple_cookie');

    return Response::json([
        'simple_cookie' => $simpleCookie,
        'timed_cookie' => $timedCookie,
        'has_simple' => $hasSimple,
        'all_cookies' => $allCookies,
    ]);
});

$router->get('/cookie/delete', function (Request $request) {
    $response = Response::json(['message' => 'Cookie удалена']);

    // Удаление cookie
    $response->deleteCookie('simple_cookie');

    return $response;
});

// ============================================
// 2. Работа с Cookies - продвинутый способ
// ============================================

$router->get('/cookie/advanced', function (Request $request) {
    $response = Response::json(['message' => 'Advanced cookies установлены']);

    // Установка с массивом опций
    $response->setCookie('session_id', 'abc123xyz', [
        'expires' => 86400,      // 24 часа
        'path' => '/',
        'domain' => '',
        'secure' => true,        // Только HTTPS
        'httpOnly' => true,      // Недоступна для JavaScript
        'sameSite' => 'Strict',   // CSRF защита
    ]);

    return $response;
});

$router->get('/cookie/object', function (Request $request) {
    $response = Response::json(['message' => 'Cookie object установлена']);

    // Использование объекта Cookie с named arguments
    $cookie = new Cookie(
        name: 'auth_token',
        value: 'secure_token_123',
        expires: 7200,
        path: '/api',
        secure: true,
        httpOnly: true,
        sameSite: 'Strict'
    );

    // Или через fluent interface
    $cookie2 = Cookie::create(name: 'session', value: 'xyz', expires: 3600)
        ->secure(true)
        ->httpOnly(true)
        ->withSameSite('Strict')
        ->withPath('/api');

    $response = $response->withCookie($cookie);

    return $response;
});

// ============================================
// 3. Определение IP адреса клиента
// ============================================

$router->get('/security/ip', function (Request $request) {
    // Базовое определение (без учета прокси)
    $basicIp = $request->getClientIp(false);

    // С учетом прокси (для production за Nginx/Cloudflare)
    $realIp = $request->getClientIp(true);

    return Response::json([
        'basic_ip' => $basicIp,
        'real_ip_with_proxy' => $realIp,
        'remote_addr' => $request->getServerParam('REMOTE_ADDR'),
        'x_forwarded_for' => $request->getServerParam('HTTP_X_FORWARDED_FOR'),
        'cf_connecting_ip' => $request->getServerParam('HTTP_CF_CONNECTING_IP'),
    ]);
});

// ============================================
// 4. Проверка HTTPS соединения
// ============================================

$router->get('/security/https', function (Request $request) {
    $isSecure = $request->isSecure();
    $scheme = $request->getScheme();
    $fullUrl = $request->getFullUrl();

    $message = $isSecure ? '✓ Соединение защищено (HTTPS)' : '✗ Незащищенное соединение (HTTP)';

    return Response::json([
        'is_secure' => $isSecure,
        'scheme' => $scheme,
        'full_url' => $fullUrl,
        'message' => $message,
        'server_port' => $request->getServerParam('SERVER_PORT'),
        'https_var' => $request->getServerParam('HTTPS'),
    ]);
});

// ============================================
// 5. HTTP Method Override для REST API
// ============================================

// Пример HTML формы с method override
$router->get('/form/delete-user', function () {
    $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Method Override Example</title>
</head>
<body>
    <h1>Delete User Form</h1>
    <p>HTML формы поддерживают только GET и POST, но мы можем эмулировать DELETE:</p>
    
    <form method="POST" action="/users/123">
        <!-- Скрытое поле _method для override -->
        <input type="hidden" name="_method" value="DELETE">
        
        <p>Вы уверены что хотите удалить пользователя #123?</p>
        <button type="submit">Удалить пользователя</button>
    </form>
    
    <hr>
    
    <h2>Как это работает:</h2>
    <ol>
        <li>Форма отправляет POST запрос</li>
        <li>Router читает поле _method=DELETE</li>
        <li>Router меняет метод с POST на DELETE</li>
        <li>Обрабатывается как DELETE /users/123</li>
    </ol>
</body>
</html>
HTML;
    return Response::html($html);
});

// Этот маршрут будет вызван при отправке формы выше
$router->delete('/users/{id}', function (Request $request) {
    $userId = $request->getParam('id');

    return Response::json([
        'message' => 'User deleted via method override',
        'user_id' => $userId,
        'original_method' => 'POST (with _method=DELETE)',
        'effective_method' => $request->getMethod(),
    ]);
});

// API endpoint с поддержкой X-HTTP-Method-Override header
$router->post('/api/users/{id}', function (Request $request) {
    // Если пришел header X-HTTP-Method-Override: DELETE
    // то метод будет DELETE, иначе POST

    $method = $request->getMethod();

    if ($method === 'DELETE') {
        return Response::json([
            'message' => 'User deleted via header override',
            'user_id' => $request->getParam('id'),
        ]);
    }

    return Response::json([
        'message' => 'Regular POST request',
        'user_id' => $request->getParam('id'),
    ]);
});

// ============================================
// 6. Пример аутентификации через Cookies
// ============================================

$router->post('/auth/login', function (Request $request) {
    $email = $request->input('email');
    $password = $request->input('password');

    // Здесь должна быть реальная проверка пользователя
    // Для примера просто создаем токен
    $token = bin2hex(random_bytes(32));

    $response = Response::json([
        'message' => 'Login successful',
        'user' => [
            'email' => $email,
            'token' => $token,
        ],
    ]);

    // Устанавливаем session cookie с токеном
    $response->setCookie('auth_token', $token, [
        'expires' => 86400 * 7,  // 7 дней
        'httpOnly' => true,      // Защита от XSS
        'secure' => true,        // Только HTTPS
        'sameSite' => 'Strict',   // Защита от CSRF
    ]);

    return $response;
});

$router->get('/auth/me', function (Request $request) {
    $token = $request->getCookie('auth_token');

    if (!$token) {
        return Response::json([
            'error' => 'Unauthorized',
            'message' => 'No auth token found',
        ], 401);
    }

    // Здесь должна быть проверка токена
    // Для примера просто возвращаем успех
    return Response::json([
        'user' => [
            'id' => 123,
            'email' => 'user@example.com',
            'token' => $token,
        ],
    ]);
});

$router->post('/auth/logout', function (Request $request) {
    $response = Response::json(['message' => 'Logged out']);

    // Удаляем auth cookie
    $response->deleteCookie('auth_token');

    return $response;
});

// ============================================
// 7. Полная информация о запросе
// ============================================

$router->get('/debug/request-info', function (Request $request) {
    return Response::json([
        'method' => $request->getMethod(),
        'uri' => $request->getUri(),
        'path' => $request->getPath(),
        'scheme' => $request->getScheme(),
        'host' => $request->getHost(),
        'full_url' => $request->getFullUrl(),
        'is_secure' => $request->isSecure(),
        'is_ajax' => $request->isAjax(),
        'is_json' => $request->isJson(),
        'client_ip' => $request->getClientIp(true),
        'cookies' => $request->getCookies(),
        'headers' => $request->getHeaders(),
    ]);
});

// ============================================
// Запуск роутера
// ============================================

try {
    $router->run();
} catch (Exception $e) {
    Response::json([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
    ], 500)->send();
}

// ============================================
// Примеры использования через curl:
// ============================================

/*

# 1. Установка cookies
curl http://localhost:8000/cookie/set -c cookies.txt

# 2. Чтение cookies
curl http://localhost:8000/cookie/get -b cookies.txt

# 3. Удаление cookies
curl http://localhost:8000/cookie/delete -b cookies.txt -c cookies.txt

# 4. Advanced cookies
curl https://localhost:8443/cookie/advanced

# 5. IP адрес
curl http://localhost:8000/security/ip
curl -H "X-Forwarded-For: 1.2.3.4" http://localhost:8000/security/ip

# 6. HTTPS проверка
curl https://localhost:8443/security/https

# 7. Method Override через POST
curl -X POST http://localhost:8000/users/123 \
  -d "_method=DELETE"

# 8. Method Override через header
curl -X POST http://localhost:8000/api/users/456 \
  -H "X-HTTP-Method-Override: DELETE"

# 9. Login
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret"}' \
  -c cookies.txt

# 10. Check auth
curl http://localhost:8000/auth/me -b cookies.txt

# 11. Logout
curl -X POST http://localhost:8000/auth/logout -b cookies.txt -c cookies.txt

# 12. Debug info
curl http://localhost:8000/debug/request-info

*/
