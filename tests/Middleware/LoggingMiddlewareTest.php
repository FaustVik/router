<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Middleware\LoggingMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для класса LoggingMiddleware
 */
final class LoggingMiddlewareTest extends TestCase
{
    private string $testLogFile;

    protected function setUp(): void
    {
        $this->testLogFile = sys_get_temp_dir() . '/router_test_' . uniqid() . '.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testLogFile)) {
            @unlink($this->testLogFile);
        }
    }

    public function testLogsToFile(): void
    {
        $middleware = new LoggingMiddleware($this->testLogFile);
        $request = new Request('GET', '/api/users', [], [], ['User-Agent' => 'TestAgent'], ['REMOTE_ADDR' => '127.0.0.1']);

        $response = Response::json(['ok' => true], 200);

        $result = $middleware->handle($request, fn () => $response);

        $this->assertSame($response, $result);
        $this->assertFileExists($this->testLogFile);

        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('GET', $logContent);
        $this->assertStringContainsString('/api/users', $logContent);
        $this->assertStringContainsString('200', $logContent);
        $this->assertStringContainsString('OK', $logContent);
    }

    public function testLogsWithUserAgent(): void
    {
        $middleware = new LoggingMiddleware($this->testLogFile, null, true, false);
        $request = new Request('POST', '/api/login', [], [], ['User-Agent' => 'Mozilla/5.0']);

        $response = Response::json([], 201);

        $middleware->handle($request, fn () => $response);

        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Mozilla/5.0', $logContent);
    }

    public function testLogsWithoutUserAgent(): void
    {
        $middleware = new LoggingMiddleware($this->testLogFile, null, false, true);
        $request = new Request('GET', '/api/test', [], [], ['User-Agent' => 'TestAgent']);

        $response = new Response('', 200);

        $middleware->handle($request, fn () => $response);

        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringNotContainsString('TestAgent', $logContent);
    }

    public function testLogsWithIp(): void
    {
        $middleware = new LoggingMiddleware($this->testLogFile, null, false, true);
        $request = new Request('GET', '/api/test', [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);

        $response = new Response('', 200);

        $middleware->handle($request, fn () => $response);

        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('192.168.1.100', $logContent);
    }

    public function testLogsWithoutIp(): void
    {
        $middleware = new LoggingMiddleware($this->testLogFile, null, false, false);
        $request = new Request('GET', '/api/test', [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);

        $response = new Response('', 200);

        $middleware->handle($request, fn () => $response);

        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringNotContainsString('192.168.1.100', $logContent);
    }

    public function testLogsDuration(): void
    {
        $middleware = new LoggingMiddleware($this->testLogFile);
        $request = new Request('GET', '/api/slow');

        $response = new Response('', 200);

        $middleware->handle($request, function () use ($response) {
            usleep(1000); // 1ms задержка
            return $response;
        });

        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('ms', $logContent);
    }

    public function testLogsVariousStatusCodes(): void
    {
        $statusCodes = [
            200 => 'OK',
            201 => 'Created',
            404 => 'Not Found',
            500 => 'Internal Server Error',
        ];

        foreach ($statusCodes as $code => $text) {
            $logFile = sys_get_temp_dir() . '/router_test_status_' . $code . '.log';

            $middleware = new LoggingMiddleware($logFile);
            $request = new Request('GET', '/test');
            $response = new Response('', $code);

            $middleware->handle($request, fn () => $response);

            $logContent = file_get_contents($logFile);
            $this->assertStringContainsString((string) $code, $logContent);
            $this->assertStringContainsString($text, $logContent);

            @unlink($logFile);
        }
    }

    public function testCustomLogger(): void
    {
        $loggedMessages = [];
        $loggedContexts = [];

        $customLogger = function (string $message, array $context) use (&$loggedMessages, &$loggedContexts): void {
            $loggedMessages[] = $message;
            $loggedContexts[] = $context;
        };

        $middleware = new LoggingMiddleware(null, $customLogger);
        $request = new Request('POST', '/api/users', [], [], [], ['REMOTE_ADDR' => '10.0.0.1']);
        $response = Response::json(['id' => 1], 201);

        $middleware->handle($request, fn () => $response);

        $this->assertCount(1, $loggedMessages);
        $this->assertCount(1, $loggedContexts);

        $context = $loggedContexts[0];
        $this->assertSame('POST', $context['method']);
        $this->assertSame('/api/users', $context['uri']);
        $this->assertSame(201, $context['status_code']);
        $this->assertSame('Created', $context['status_text']);
        $this->assertArrayHasKey('duration_ms', $context);
    }

    public function testCustomLoggerWithoutIpAndUserAgent(): void
    {
        $loggedContext = null;

        $customLogger = function (string $message, array $context) use (&$loggedContext): void {
            $loggedContext = $context;
        };

        $middleware = new LoggingMiddleware(null, $customLogger, false, false);
        $request = new Request('GET', '/test', [], [], ['User-Agent' => 'Test'], ['REMOTE_ADDR' => '1.2.3.4']);
        $response = new Response();

        $middleware->handle($request, fn () => $response);

        $this->assertNotNull($loggedContext);
        $this->assertArrayNotHasKey('ip', $loggedContext);
        $this->assertArrayNotHasKey('user_agent', $loggedContext);
    }

    public function testLogFileCreatesDirectory(): void
    {
        $logDir = sys_get_temp_dir() . '/router_logs_test_' . uniqid();
        $logFile = $logDir . '/app.log';

        $this->assertDirectoryDoesNotExist($logDir);

        $middleware = new LoggingMiddleware($logFile);
        $request = new Request('GET', '/test');
        $response = new Response();

        $middleware->handle($request, fn () => $response);

        $this->assertFileExists($logFile);

        // Cleanup
        @unlink($logFile);
        @rmdir($logDir);
    }

    public function testLogsUnknownStatusCode(): void
    {
        $middleware = new LoggingMiddleware($this->testLogFile);
        $request = new Request('GET', '/test');
        $response = new Response('', 999); // Несуществующий код

        $middleware->handle($request, fn () => $response);

        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('999', $logContent);
        $this->assertStringContainsString('Unknown', $logContent);
    }

    public function testLogsMultipleRequests(): void
    {
        $middleware = new LoggingMiddleware($this->testLogFile);

        for ($i = 1; $i <= 3; $i++) {
            $request = new Request('GET', '/api/test' . $i);
            $response = new Response('', 200);
            $middleware->handle($request, fn () => $response);
        }

        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('/api/test1', $logContent);
        $this->assertStringContainsString('/api/test2', $logContent);
        $this->assertStringContainsString('/api/test3', $logContent);
    }
}
