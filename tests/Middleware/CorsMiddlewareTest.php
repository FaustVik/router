<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Middleware\CorsMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для класса CorsMiddleware
 */
final class CorsMiddlewareTest extends TestCase
{
    public function testOptionsPreflightRequest(): void
    {
        $middleware = new CorsMiddleware();
        $request = new Request('OPTIONS', '/api/users', [], [], ['Origin' => 'https://example.com']);

        $response = $middleware->handle($request, function () {
            $this->fail('Next should not be called for OPTIONS');
            return new Response();
        });

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testPreflightWithAllowedOrigin(): void
    {
        $middleware = new CorsMiddleware(['https://example.com']);
        $request = new Request('OPTIONS', '/api/users', [], [], ['Origin' => 'https://example.com']);

        $response = $middleware->handle($request, fn() => new Response());

        $this->assertSame('https://example.com', $response->getHeader('Access-Control-Allow-Origin'));
    }

    public function testPreflightWithWildcardOrigin(): void
    {
        $middleware = new CorsMiddleware(['*']);
        $request = new Request('OPTIONS', '/api/users', [], [], ['Origin' => 'https://example.com']);

        $response = $middleware->handle($request, fn() => new Response());

        $this->assertSame('*', $response->getHeader('Access-Control-Allow-Origin'));
    }

    public function testPreflightIncludesAllowedMethods(): void
    {
        $middleware = new CorsMiddleware(
            allowedMethods: ['GET', 'POST', 'DELETE']
        );
        $request = new Request('OPTIONS', '/api/users', [], [], ['Origin' => 'https://example.com']);

        $response = $middleware->handle($request, fn() => new Response());

        $allowMethods = $response->getHeader('Access-Control-Allow-Methods');
        $this->assertStringContainsString('GET', $allowMethods);
        $this->assertStringContainsString('POST', $allowMethods);
        $this->assertStringContainsString('DELETE', $allowMethods);
    }

    public function testPreflightIncludesAllowedHeaders(): void
    {
        $middleware = new CorsMiddleware(
            allowedHeaders: ['Content-Type', 'Authorization', 'X-Custom']
        );
        $request = new Request('OPTIONS', '/api/users', [], [], ['Origin' => 'https://example.com']);

        $response = $middleware->handle($request, fn() => new Response());

        $allowHeaders = $response->getHeader('Access-Control-Allow-Headers');
        $this->assertStringContainsString('Content-Type', $allowHeaders);
        $this->assertStringContainsString('Authorization', $allowHeaders);
        $this->assertStringContainsString('X-Custom', $allowHeaders);
    }

    public function testPreflightIncludesMaxAge(): void
    {
        $middleware = new CorsMiddleware(maxAge: 7200);
        $request = new Request('OPTIONS', '/api/users', [], [], ['Origin' => 'https://example.com']);

        $response = $middleware->handle($request, fn() => new Response());

        $this->assertSame('7200', $response->getHeader('Access-Control-Max-Age'));
    }

    public function testPreflightWithCredentials(): void
    {
        $middleware = new CorsMiddleware(
            allowedOrigins: ['https://example.com'],
            allowCredentials: true
        );
        $request = new Request('OPTIONS', '/api/users', [], [], ['Origin' => 'https://example.com']);

        $response = $middleware->handle($request, fn() => new Response());

        $this->assertSame('true', $response->getHeader('Access-Control-Allow-Credentials'));
        $this->assertSame('https://example.com', $response->getHeader('Access-Control-Allow-Origin'));
    }

    public function testPreflightWithExposedHeaders(): void
    {
        $middleware = new CorsMiddleware(
            exposedHeaders: ['X-Total-Count', 'X-Page-Number']
        );
        $request = new Request('OPTIONS', '/api/users', [], [], ['Origin' => 'https://example.com']);

        $response = $middleware->handle($request, fn() => new Response());

        $exposedHeaders = $response->getHeader('Access-Control-Expose-Headers');
        $this->assertStringContainsString('X-Total-Count', $exposedHeaders);
        $this->assertStringContainsString('X-Page-Number', $exposedHeaders);
    }

    public function testRegularRequestAddsCorsHeaders(): void
    {
        $middleware = new CorsMiddleware();
        $request = new Request('GET', '/api/users', [], [], ['Origin' => 'https://example.com']);

        $nextResponse = Response::json(['users' => []]);

        $response = $middleware->handle($request, fn() => $nextResponse);

        $this->assertSame($nextResponse->getContent(), $response->getContent());
        $this->assertSame('*', $response->getHeader('Access-Control-Allow-Origin'));
    }

    public function testRegularRequestWithSpecificOrigin(): void
    {
        $middleware = new CorsMiddleware(['https://example.com', 'https://app.example.com']);
        $request = new Request('POST', '/api/users', [], [], ['Origin' => 'https://example.com']);

        $nextResponse = new Response('', 201);

        $response = $middleware->handle($request, fn() => $nextResponse);

        $this->assertSame('https://example.com', $response->getHeader('Access-Control-Allow-Origin'));
    }

    public function testRegularRequestWithCredentials(): void
    {
        $middleware = new CorsMiddleware(
            allowedOrigins: ['https://example.com'],
            allowCredentials: true
        );
        $request = new Request('GET', '/api/profile', [], [], ['Origin' => 'https://example.com']);

        $response = $middleware->handle($request, fn() => new Response());

        $this->assertSame('true', $response->getHeader('Access-Control-Allow-Credentials'));
    }

    public function testDisallowedOriginDoesNotAddHeaders(): void
    {
        $middleware = new CorsMiddleware(['https://allowed.com']);
        $request = new Request('GET', '/api/users', [], [], ['Origin' => 'https://evil.com']);

        $response = $middleware->handle($request, fn() => new Response());

        $this->assertNull($response->getHeader('Access-Control-Allow-Origin'));
    }

    public function testWildcardOriginPattern(): void
    {
        $middleware = new CorsMiddleware(['https://*.example.com']);
        $request = new Request('GET', '/api/test', [], [], ['Origin' => 'https://app.example.com']);

        $response = $middleware->handle($request, fn() => new Response());

        $this->assertSame('https://app.example.com', $response->getHeader('Access-Control-Allow-Origin'));
    }

    public function testWildcardOriginPatternMismatch(): void
    {
        $middleware = new CorsMiddleware(['https://*.example.com']);
        $request = new Request('GET', '/api/test', [], [], ['Origin' => 'https://other.com']);

        $response = $middleware->handle($request, fn() => new Response());

        $this->assertNull($response->getHeader('Access-Control-Allow-Origin'));
    }

    public function testSetAllowedOrigins(): void
    {
        $middleware = new CorsMiddleware();
        $middleware->setAllowedOrigins(['https://new-origin.com']);

        $request = new Request('OPTIONS', '/api/test', [], [], ['Origin' => 'https://new-origin.com']);

        $response = $middleware->handle($request, fn() => new Response());

        $this->assertSame('https://new-origin.com', $response->getHeader('Access-Control-Allow-Origin'));
    }

    public function testSetAllowedMethods(): void
    {
        $middleware = new CorsMiddleware();
        $middleware->setAllowedMethods(['GET', 'POST']);

        $request = new Request('OPTIONS', '/api/test', [], [], ['Origin' => 'https://example.com']);

        $response = $middleware->handle($request, fn() => new Response());

        $allowMethods = $response->getHeader('Access-Control-Allow-Methods');
        $this->assertStringContainsString('GET', $allowMethods);
        $this->assertStringContainsString('POST', $allowMethods);
        $this->assertStringNotContainsString('DELETE', $allowMethods);
    }

    public function testSetAllowedHeaders(): void
    {
        $middleware = new CorsMiddleware();
        $middleware->setAllowedHeaders(['X-Custom-Header']);

        $request = new Request('OPTIONS', '/api/test', [], [], ['Origin' => 'https://example.com']);

        $response = $middleware->handle($request, fn() => new Response());

        $this->assertSame('X-Custom-Header', $response->getHeader('Access-Control-Allow-Headers'));
    }

    public function testSetExposedHeaders(): void
    {
        $middleware = new CorsMiddleware();
        $middleware->setExposedHeaders(['X-Total-Count']);

        $request = new Request('OPTIONS', '/api/test', [], [], ['Origin' => 'https://example.com']);

        $response = $middleware->handle($request, fn() => new Response());

        $this->assertSame('X-Total-Count', $response->getHeader('Access-Control-Expose-Headers'));
    }

    public function testFluentInterface(): void
    {
        $middleware = new CorsMiddleware();

        $result = $middleware
            ->setAllowedOrigins(['https://example.com'])
            ->setAllowedMethods(['GET', 'POST'])
            ->setAllowedHeaders(['Content-Type'])
            ->setExposedHeaders(['X-Total']);

        $this->assertSame($middleware, $result);
    }

    public function testRequestWithoutOriginHeader(): void
    {
        $middleware = new CorsMiddleware();
        $request = new Request('GET', '/api/users');

        $nextResponse = Response::json(['data' => 'test']);

        $response = $middleware->handle($request, fn() => $nextResponse);

        // Когда нет Origin, заголовки CORS все равно добавляются для wildcard
        $this->assertSame('*', $response->getHeader('Access-Control-Allow-Origin'));
    }

    public function testCredentialsRequireSpecificOrigin(): void
    {
        $middleware = new CorsMiddleware(
            allowedOrigins: ['*'],
            allowCredentials: true
        );
        $request = new Request('GET', '/api/test', [], [], ['Origin' => 'https://example.com']);

        $response = $middleware->handle($request, fn() => new Response());

        // С credentials не должно быть *, только конкретный origin
        $this->assertSame('https://example.com', $response->getHeader('Access-Control-Allow-Origin'));
        $this->assertSame('true', $response->getHeader('Access-Control-Allow-Credentials'));
    }
}

