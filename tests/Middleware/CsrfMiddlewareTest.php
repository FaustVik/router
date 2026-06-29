<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Middleware\CsrfMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для CsrfMiddleware
 */
final class CsrfMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        // Очищаем сессию перед каждым тестом
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Очищаем сессию после каждого теста
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public function testAllowsGetRequestsWithoutToken(): void
    {
        $middleware = new CsrfMiddleware();
        $request = new Request('GET', '/test', [], [], [], []);
        $next = fn (Request $req) => new Response('OK');

        $response = $middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function testGeneratesTokenAutomatically(): void
    {
        $middleware = new CsrfMiddleware();
        $request = new Request('GET', '/test', [], [], [], []);
        $next = fn (Request $req) => new Response('OK');

        $middleware->handle($request, $next);

        $this->assertNotEmpty($_SESSION['_csrf_token']);
        $this->assertEquals(64, strlen($_SESSION['_csrf_token'])); // 32 bytes * 2 (hex)
    }

    public function testBlocksPostRequestWithoutToken(): void
    {
        $middleware = new CsrfMiddleware();
        $request = new Request('POST', '/test', [], [], [], []);
        $next = fn (Request $req) => new Response('OK');

        $response = $middleware->handle($request, $next);

        $this->assertEquals(419, $response->getStatusCode());
        $this->assertStringContainsString('CSRF Token Mismatch', $response->getContent());
    }

    public function testAllowsPostRequestWithValidToken(): void
    {
        $middleware = new CsrfMiddleware();

        // Генерируем токен через GET запрос
        $getRequest = new Request('GET', '/test', [], [], [], []);
        $next = fn (Request $req) => new Response('OK');
        $middleware->handle($getRequest, $next);

        $token = $_SESSION['_csrf_token'];

        // POST запрос с валидным токеном в body
        $postRequest = (new Request('POST', '/test', [], [], [], []))
            ->withBody(['_csrf_token' => $token]);
        $response = $middleware->handle($postRequest, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBlocksPostRequestWithInvalidToken(): void
    {
        $middleware = new CsrfMiddleware();

        // Генерируем токен
        $getRequest = new Request('GET', '/test', [], [], [], []);
        $next = fn (Request $req) => new Response('OK');
        $middleware->handle($getRequest, $next);

        // POST запрос с неправильным токеном в body
        $postRequest = (new Request('POST', '/test', [], [], [], []))
            ->withBody(['_csrf_token' => 'invalid_token']);
        $response = $middleware->handle($postRequest, $next);

        $this->assertEquals(419, $response->getStatusCode());
    }

    public function testAcceptsTokenFromHeader(): void
    {
        $middleware = new CsrfMiddleware();

        // Генерируем токен
        $getRequest = new Request('GET', '/test', [], [], [], []);
        $next = fn (Request $req) => new Response('OK');
        $middleware->handle($getRequest, $next);

        $token = $_SESSION['_csrf_token'];

        // POST запрос с токеном в заголовке
        $postRequest = new Request('POST', '/test', [], [], ['X-CSRF-Token' => $token], []);
        $response = $middleware->handle($postRequest, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAcceptsTokenFromQueryParameter(): void
    {
        $middleware = new CsrfMiddleware();

        // Генерируем токен
        $getRequest = new Request('GET', '/test', [], [], [], []);
        $next = fn (Request $req) => new Response('OK');
        $middleware->handle($getRequest, $next);

        $token = $_SESSION['_csrf_token'];

        // POST запрос с токеном в query параметрах (передаем в URI и в массиве query)
        $postRequest = new Request(
            'POST',
            '/test?_csrf_token=' . $token,
            [],
            ['_csrf_token' => $token], // query параметры
            [],
            []
        );
        $response = $middleware->handle($postRequest, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testChecksAllMutatingMethods(): void
    {
        $middleware = new CsrfMiddleware();
        $next = fn (Request $req) => new Response('OK');

        // Генерируем токен
        $getRequest = new Request('GET', '/test', [], [], [], []);
        $middleware->handle($getRequest, $next);

        $methods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        foreach ($methods as $method) {
            // Без токена - должен блокировать
            $request = new Request($method, '/test', [], [], [], []);
            $response = $middleware->handle($request, $next);
            $this->assertEquals(419, $response->getStatusCode(), "Method {$method} should require CSRF token");
        }
    }

    public function testExcludesSpecifiedPaths(): void
    {
        $middleware = new CsrfMiddleware(
            excludePaths: ['/api/webhook', '/public/']
        );

        $next = fn (Request $req) => new Response('OK');

        // POST к исключенному пути без токена должен пройти
        $request1 = new Request('POST', '/api/webhook', [], [], [], []);
        $response1 = $middleware->handle($request1, $next);
        $this->assertEquals(200, $response1->getStatusCode());

        // POST к пути начинающемуся с исключенного префикса
        $request2 = new Request('POST', '/public/upload', [], [], [], []);
        $response2 = $middleware->handle($request2, $next);
        $this->assertEquals(200, $response2->getStatusCode());

        // POST к обычному пути без токена должен быть заблокирован
        $request3 = new Request('POST', '/api/users', [], [], [], []);
        $response3 = $middleware->handle($request3, $next);
        $this->assertEquals(419, $response3->getStatusCode());
    }

    public function testCustomSessionKey(): void
    {
        $customKey = 'my_custom_csrf_key';
        $middleware = new CsrfMiddleware(sessionKey: $customKey);

        $request = new Request('GET', '/test', [], [], [], []);
        $next = fn (Request $req) => new Response('OK');

        $middleware->handle($request, $next);

        $this->assertArrayHasKey($customKey, $_SESSION);
        $this->assertNotEmpty($_SESSION[$customKey]);
    }

    public function testCustomTokenLength(): void
    {
        $tokenLength = 16;
        $middleware = new CsrfMiddleware(tokenLength: $tokenLength);

        $request = new Request('GET', '/test', [], [], [], []);
        $next = fn (Request $req) => new Response('OK');

        $middleware->handle($request, $next);

        $this->assertEquals($tokenLength * 2, strlen($_SESSION['_csrf_token'])); // hex doubles the length
    }

    public function testRegenerateToken(): void
    {
        $middleware = new CsrfMiddleware();

        $request = new Request('GET', '/test', [], [], [], []);
        $next = fn (Request $req) => new Response('OK');

        $middleware->handle($request, $next);
        $oldToken = $_SESSION['_csrf_token'];

        $newToken = $middleware->regenerateToken();

        $this->assertNotEquals($oldToken, $newToken);
        $this->assertEquals($newToken, $_SESSION['_csrf_token']);
    }

    public function testGetTokenStaticMethod(): void
    {
        $token = CsrfMiddleware::getToken();

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token));
        $this->assertEquals($token, $_SESSION['_csrf_token']);
    }

    public function testGetTokenFieldMethod(): void
    {
        CsrfMiddleware::getToken(); // Генерируем токен
        $field = CsrfMiddleware::getTokenField();

        $this->assertStringContainsString('<input', $field);
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="_csrf_token"', $field);
        $this->assertStringContainsString('value=', $field);
        $this->assertStringContainsString($_SESSION['_csrf_token'], $field);
    }

    public function testGetTokenMetaMethod(): void
    {
        CsrfMiddleware::getToken(); // Генерируем токен
        $meta = CsrfMiddleware::getTokenMeta();

        $this->assertStringContainsString('<meta', $meta);
        $this->assertStringContainsString('name="csrf-token"', $meta);
        $this->assertStringContainsString('content=', $meta);
        $this->assertStringContainsString($_SESSION['_csrf_token'], $meta);
    }

    public function testAddsTokenToRequestAttributes(): void
    {
        $middleware = new CsrfMiddleware();
        $request = new Request('GET', '/test', [], [], [], []);
        $receivedRequest = null;

        $next = function (Request $req) use (&$receivedRequest): Response {
            $receivedRequest = $req;
            return new Response('OK');
        };

        $middleware->handle($request, $next);

        $this->assertNotNull($receivedRequest);
        $this->assertNotNull($receivedRequest->getAttribute('csrf_token'));
        $this->assertEquals($_SESSION['_csrf_token'], $receivedRequest->getAttribute('csrf_token'));
    }

    public function testProtectsAgainstTimingAttacks(): void
    {
        $middleware = new CsrfMiddleware();

        // Генерируем токен
        $getRequest = new Request('GET', '/test', [], [], [], []);
        $next = fn (Request $req) => new Response('OK');
        $middleware->handle($getRequest, $next);

        $validToken = $_SESSION['_csrf_token'];
        $invalidToken = str_repeat('a', strlen($validToken));

        // Замеряем время проверки валидного токена
        $startValid = microtime(true);
        $postRequest1 = new Request('POST', '/test', ['_csrf_token' => $validToken], [], [], []);
        $middleware->handle($postRequest1, $next);
        $timeValid = microtime(true) - $startValid;

        // Замеряем время проверки невалидного токена
        $startInvalid = microtime(true);
        $postRequest2 = new Request('POST', '/test', ['_csrf_token' => $invalidToken], [], [], []);
        $middleware->handle($postRequest2, $next);
        $timeInvalid = microtime(true) - $startInvalid;

        // hash_equals должен иметь константное время выполнения
        // Разница не должна быть слишком большой (порядок величины)
        $this->assertLessThan($timeValid * 100, $timeInvalid);
    }

    public function testHtmlEntityEscapingInTokenField(): void
    {
        // Закрываем сессию если открыта
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Устанавливаем опасное значение токена
        $_SESSION = [];
        $_SESSION['_csrf_token'] = '<script>alert("xss")</script>';

        // Имитируем активную сессию
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['_csrf_token'] = '<script>alert("xss")</script>';

        $field = CsrfMiddleware::getTokenField();

        // Проверяем что опасные символы экранированы
        $this->assertStringNotContainsString('<script>', $field);
        $this->assertStringContainsString('&lt;script&gt;', $field);
    }

    public function testHtmlEntityEscapingInTokenMeta(): void
    {
        // Закрываем сессию если открыта
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Устанавливаем опасное значение токена
        $_SESSION = [];
        $_SESSION['_csrf_token'] = '<script>alert("xss")</script>';

        // Имитируем активную сессию
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['_csrf_token'] = '<script>alert("xss")</script>';

        $meta = CsrfMiddleware::getTokenMeta();

        // Проверяем что опасные символы экранированы
        $this->assertStringNotContainsString('<script>', $meta);
        $this->assertStringContainsString('&lt;script&gt;', $meta);
    }

    public function testResponseContainsCsrfProtectionHeader(): void
    {
        $middleware = new CsrfMiddleware();
        $request = new Request('POST', '/test', [], [], [], []);
        $next = fn (Request $req) => new Response('OK');

        $response = $middleware->handle($request, $next);

        $this->assertEquals('token-mismatch', $response->getHeader('X-CSRF-Protection'));
    }
}
