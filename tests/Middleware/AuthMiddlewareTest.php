<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Middleware\AuthMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для класса AuthMiddleware
 */
final class AuthMiddlewareTest extends TestCase
{
    private function createDefaultValidator(): callable
    {
        return function (string $token): ?array {
            return match ($token) {
                'admin-token' => ['id' => 1, 'username' => 'admin', 'role' => 'admin'],
                'user-token' => ['id' => 2, 'username' => 'user', 'role' => 'user'],
                default => null
            };
        };
    }

    public function testMissingAuthorizationHeader(): void
    {
        $middleware = new AuthMiddleware($this->createDefaultValidator());
        $request = new Request('GET', '/api/users');

        $response = $middleware->handle($request, function () {
            $this->fail('Next should not be called');
            return new Response();
        });

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeader('Content-Type', ''));

        $content = json_decode($response->getContent(), true);
        $this->assertSame('Unauthorized', $content['error']);
        $this->assertStringContainsString('required', $content['message']);
    }

    public function testInvalidAuthorizationFormat(): void
    {
        $middleware = new AuthMiddleware($this->createDefaultValidator());
        $request = new Request(
            'GET',
            '/api/users',
            [],
            [],
            ['Authorization' => 'Basic abc123']
        );

        $response = $middleware->handle($request, function () {
            $this->fail('Next should not be called');
            return new Response();
        });

        $this->assertSame(401, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertStringContainsString('format', $content['message']);
    }

    public function testEmptyToken(): void
    {
        $middleware = new AuthMiddleware($this->createDefaultValidator());
        $request = new Request(
            'GET',
            '/api/users',
            [],
            [],
            ['Authorization' => 'Bearer ']
        );

        $response = $middleware->handle($request, function () {
            $this->fail('Next should not be called');
            return new Response();
        });

        $this->assertSame(401, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertStringContainsString('empty', $content['message']);
    }

    public function testInvalidToken(): void
    {
        $middleware = new AuthMiddleware($this->createDefaultValidator());
        $request = new Request(
            'GET',
            '/api/users',
            [],
            [],
            ['Authorization' => 'Bearer invalid-token']
        );

        $response = $middleware->handle($request, function () {
            $this->fail('Next should not be called');
            return new Response();
        });

        $this->assertSame(401, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Invalid or expired', $content['message']);
    }

    public function testValidAdminToken(): void
    {
        $middleware = new AuthMiddleware($this->createDefaultValidator());
        $request = new Request(
            'GET',
            '/api/users',
            [],
            [],
            ['Authorization' => 'Bearer admin-token']
        );

        $modifiedRequest = null;
        $nextResponse = Response::json(['data' => 'success']);

        $response = $middleware->handle($request, function (Request $req) use (&$modifiedRequest, $nextResponse) {
            $modifiedRequest = $req;
            return $nextResponse;
        });

        $this->assertSame($nextResponse, $response);
        $this->assertNotNull($modifiedRequest);
        $this->assertTrue($modifiedRequest->getAttribute('authenticated'));
        $this->assertSame('admin-token', $modifiedRequest->getAttribute('token'));

        $user = $modifiedRequest->getAttribute('user');
        $this->assertIsArray($user);
        $this->assertSame(1, $user['id']);
        $this->assertSame('admin', $user['role']);
    }

    public function testValidUserToken(): void
    {
        $middleware = new AuthMiddleware($this->createDefaultValidator());
        $request = new Request(
            'GET',
            '/api/profile',
            [],
            [],
            ['Authorization' => 'Bearer user-token']
        );

        $modifiedRequest = null;

        $middleware->handle($request, function (Request $req) use (&$modifiedRequest) {
            $modifiedRequest = $req;
            return new Response();
        });

        $this->assertNotNull($modifiedRequest);
        $user = $modifiedRequest->getAttribute('user');
        $this->assertSame(2, $user['id']);
        $this->assertSame('user', $user['role']);
    }

    public function testCustomTokenValidator(): void
    {
        $customValidator = function (string $token): ?array {
            return match ($token) {
                'custom-token' => ['id' => 999, 'name' => 'Custom User'],
                default => null
            };
        };

        $middleware = new AuthMiddleware($customValidator);
        $request = new Request(
            'GET',
            '/api/custom',
            [],
            [],
            ['Authorization' => 'Bearer custom-token']
        );

        $modifiedRequest = null;

        $middleware->handle($request, function (Request $req) use (&$modifiedRequest) {
            $modifiedRequest = $req;
            return new Response();
        });

        $this->assertNotNull($modifiedRequest);
        $user = $modifiedRequest->getAttribute('user');
        $this->assertSame(999, $user['id']);
        $this->assertSame('Custom User', $user['name']);
    }

    public function testCustomTokenValidatorReturnsNull(): void
    {
        $customValidator = fn(string $token) => null;

        $middleware = new AuthMiddleware($customValidator);
        $request = new Request(
            'GET',
            '/api/custom',
            [],
            [],
            ['Authorization' => 'Bearer any-token']
        );

        $response = $middleware->handle($request, function () {
            $this->fail('Next should not be called');
            return new Response();
        });

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testWwwAuthenticateHeaderInResponse(): void
    {
        $middleware = new AuthMiddleware($this->createDefaultValidator());
        $request = new Request('GET', '/api/users');

        $response = $middleware->handle($request, function () {
            return new Response();
        });

        $this->assertSame('Bearer realm="API"', $response->getHeader('WWW-Authenticate'));
    }

    public function testTokenIsAddedToRequestAttributes(): void
    {
        $middleware = new AuthMiddleware($this->createDefaultValidator());
        $request = new Request(
            'GET',
            '/api/test',
            [],
            [],
            ['Authorization' => 'Bearer admin-token']
        );

        $capturedRequest = null;

        $middleware->handle($request, function (Request $req) use (&$capturedRequest) {
            $capturedRequest = $req;
            return new Response();
        });

        $this->assertNotNull($capturedRequest);
        $this->assertTrue($capturedRequest->getAttribute('authenticated'));
        $this->assertSame('admin-token', $capturedRequest->getAttribute('token'));
        $this->assertIsArray($capturedRequest->getAttribute('user'));
    }
}

