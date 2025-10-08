<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Middleware;

use FaustVik\Router\Cache\FileCache;
use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для RateLimitMiddleware
 */
final class RateLimitMiddlewareTest extends TestCase
{
    private FileCache $cache;
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/router_rate_limit_test_' . uniqid();
        $this->cache = new FileCache(cacheDir: $this->cacheDir);
    }

    protected function tearDown(): void
    {
        // Очищаем кеш после каждого теста
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($this->cacheDir);
        }
    }

    public function testAllowsRequestsWithinLimit(): void
    {
        $middleware = new RateLimitMiddleware(
            cache: $this->cache,
            maxAttempts: 5,
            decaySeconds: 60
        );

        $request = new Request('GET', '/test', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $next = fn(Request $req) => new Response('OK');

        $response = $middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals('5', $response->getHeader('X-RateLimit-Limit'));
        $this->assertEquals('4', $response->getHeader('X-RateLimit-Remaining'));
    }

    public function testBlocksRequestsExceedingLimit(): void
    {
        $middleware = new RateLimitMiddleware(
            cache: $this->cache,
            maxAttempts: 3,
            decaySeconds: 60
        );

        $request = new Request('GET', '/test', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $next = fn(Request $req) => new Response('OK');

        // Делаем 3 разрешенных запроса
        for ($i = 0; $i < 3; $i++) {
            $response = $middleware->handle($request, $next);
            $this->assertEquals(200, $response->getStatusCode());
        }

        // 4-й запрос должен быть заблокирован
        $response = $middleware->handle($request, $next);

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertStringContainsString('Too Many Requests', $response->getContent());
        $this->assertEquals('3', $response->getHeader('X-RateLimit-Limit'));
        $this->assertEquals('0', $response->getHeader('X-RateLimit-Remaining'));
        $this->assertNotNull($response->getHeader('Retry-After'));
    }

    public function testAddsRateLimitHeaders(): void
    {
        $middleware = new RateLimitMiddleware(
            cache: $this->cache,
            maxAttempts: 10,
            decaySeconds: 60
        );

        $request = new Request('GET', '/test', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $next = fn(Request $req) => new Response('OK');

        $response = $middleware->handle($request, $next);

        $this->assertNotNull($response->getHeader('X-RateLimit-Limit'));
        $this->assertNotNull($response->getHeader('X-RateLimit-Remaining'));
        $this->assertNotNull($response->getHeader('X-RateLimit-Reset'));
    }

    public function testSeparateLimitsForDifferentIPs(): void
    {
        $middleware = new RateLimitMiddleware(
            cache: $this->cache,
            maxAttempts: 2,
            decaySeconds: 60
        );

        $next = fn(Request $req) => new Response('OK');

        // Первый IP делает 2 запроса
        $request1 = new Request('GET', '/test', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $middleware->handle($request1, $next);
        $response1 = $middleware->handle($request1, $next);
        $this->assertEquals(200, $response1->getStatusCode());

        // Второй IP должен иметь свой лимит
        $request2 = new Request('GET', '/test', [], [], [], ['REMOTE_ADDR' => '192.168.1.1']);
        $response2 = $middleware->handle($request2, $next);
        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertEquals('1', $response2->getHeader('X-RateLimit-Remaining'));
    }

    public function testSeparateLimitsPerPathWhenEnabled(): void
    {
        $middleware = new RateLimitMiddleware(
            cache: $this->cache,
            maxAttempts: 2,
            decaySeconds: 60,
            includePathInKey: true
        );

        $next = fn(Request $req) => new Response('OK');

        // Исчерпываем лимит на /api/users
        $request1 = new Request('GET', '/api/users', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $middleware->handle($request1, $next);
        $response1 = $middleware->handle($request1, $next);
        $this->assertEquals(200, $response1->getStatusCode());

        // /api/posts должен иметь свой лимит
        $request2 = new Request('GET', '/api/posts', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $response2 = $middleware->handle($request2, $next);
        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertEquals('1', $response2->getHeader('X-RateLimit-Remaining'));
    }

    public function testClearLimits(): void
    {
        $middleware = new RateLimitMiddleware(
            cache: $this->cache,
            maxAttempts: 2,
            decaySeconds: 60
        );

        $request = new Request('GET', '/test', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $next = fn(Request $req) => new Response('OK');

        // Исчерпываем лимит
        $middleware->handle($request, $next);
        $middleware->handle($request, $next);
        $response = $middleware->handle($request, $next);
        $this->assertEquals(429, $response->getStatusCode());

        // Очищаем лимиты
        $middleware->clearLimits();

        // Теперь запросы снова разрешены
        $response = $middleware->handle($request, $next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetLimitStatus(): void
    {
        $middleware = new RateLimitMiddleware(
            cache: $this->cache,
            maxAttempts: 5,
            decaySeconds: 60
        );

        $request = new Request('GET', '/test', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $next = fn(Request $req) => new Response('OK');

        // Начальный статус
        $status = $middleware->getLimitStatus($request);
        $this->assertEquals(0, $status['attempts']);
        $this->assertEquals(5, $status['remaining']);

        // После одного запроса
        $middleware->handle($request, $next);
        $status = $middleware->getLimitStatus($request);
        $this->assertEquals(1, $status['attempts']);
        $this->assertEquals(4, $status['remaining']);
        $this->assertIsInt($status['reset_time']);
    }

    public function testRemainingNeverGoesNegative(): void
    {
        $middleware = new RateLimitMiddleware(
            cache: $this->cache,
            maxAttempts: 1,
            decaySeconds: 60
        );

        $request = new Request('GET', '/test', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $next = fn(Request $req) => new Response('OK');

        // Делаем несколько запросов сверх лимита
        for ($i = 0; $i < 5; $i++) {
            $response = $middleware->handle($request, $next);
            $remaining = (int) $response->getHeader('X-RateLimit-Remaining');
            $this->assertGreaterThanOrEqual(0, $remaining);
        }
    }

    public function testRetryAfterHeaderOnLimitExceeded(): void
    {
        $middleware = new RateLimitMiddleware(
            cache: $this->cache,
            maxAttempts: 1,
            decaySeconds: 60
        );

        $request = new Request('GET', '/test', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $next = fn(Request $req) => new Response('OK');

        // Исчерпываем лимит
        $middleware->handle($request, $next);
        $response = $middleware->handle($request, $next);

        $this->assertEquals(429, $response->getStatusCode());
        $retryAfter = $response->getHeader('Retry-After');
        $this->assertNotNull($retryAfter);
        $this->assertIsNumeric($retryAfter);
        $this->assertGreaterThan(0, (int) $retryAfter);
    }

    public function testHandlesGetClientIpCorrectly(): void
    {
        $middleware = new RateLimitMiddleware(
            cache: $this->cache,
            maxAttempts: 2,
            decaySeconds: 60
        );

        $next = fn(Request $req) => new Response('OK');

        // Тестируем различные способы получения IP
        $request1 = new Request('GET', '/test', [], [], [], ['REMOTE_ADDR' => '10.0.0.1']);
        $response1 = $middleware->handle($request1, $next);
        $this->assertEquals(200, $response1->getStatusCode());

        // Тестируем что лимит применяется к конкретному IP
        $response2 = $middleware->handle($request1, $next);
        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertEquals('0', $response2->getHeader('X-RateLimit-Remaining'));
    }
}

