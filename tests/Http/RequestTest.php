<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Http;

use FaustVik\Router\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для класса Request
 */
final class RequestTest extends TestCase
{
    public function testConstructorSetsValues(): void
    {
        $request = new Request(
            'POST',
            '/users/123',
            ['id' => '123'],
            ['page' => '1'],
            ['Content-Type' => 'application/json'],
            ['SERVER_NAME' => 'localhost']
        );

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/users/123', $request->getUri());
        $this->assertSame(['id' => '123'], $request->getParams());
        $this->assertSame(['page' => '1'], $request->getQuery());
        $this->assertSame(['Content-Type' => 'application/json'], $request->getHeaders());
        $this->assertSame(['SERVER_NAME' => 'localhost'], $request->getServer());
    }

    public function testConstructorWithDefaultValues(): void
    {
        $request = new Request();

        $this->assertSame('', $request->getMethod());
        $this->assertSame('', $request->getUri());
        $this->assertSame([], $request->getParams());
        $this->assertSame([], $request->getQuery());
        $this->assertSame([], $request->getHeaders());
        $this->assertSame([], $request->getServer());
    }

    public function testGetParam(): void
    {
        $request = new Request(
            'GET',
            '/users/123',
            ['id' => '123', 'name' => 'John']
        );

        $this->assertSame('123', $request->getParam('id'));
        $this->assertSame('John', $request->getParam('name'));
        $this->assertNull($request->getParam('age'));
        $this->assertSame(25, $request->getParam('age', 25));
    }

    public function testGetQueryParam(): void
    {
        $request = new Request(
            'GET',
            '/users',
            [],
            ['page' => '1', 'limit' => '10']
        );

        $this->assertSame('1', $request->getQueryParam('page'));
        $this->assertSame('10', $request->getQueryParam('limit'));
        $this->assertNull($request->getQueryParam('offset'));
        $this->assertSame('0', $request->getQueryParam('offset', '0'));
    }

    public function testGetHeader(): void
    {
        $request = new Request(
            'GET',
            '/',
            [],
            [],
            ['Content-Type' => 'application/json', 'Authorization' => 'Bearer token']
        );

        $this->assertSame('application/json', $request->getHeader('Content-Type'));
        $this->assertSame('Bearer token', $request->getHeader('Authorization'));
        $this->assertNull($request->getHeader('X-Custom'));
        $this->assertSame('default', $request->getHeader('X-Custom', 'default'));
    }

    public function testGetServerParam(): void
    {
        $request = new Request(
            'GET',
            '/',
            [],
            [],
            [],
            ['SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80']
        );

        $this->assertSame('localhost', $request->getServerParam('SERVER_NAME'));
        $this->assertSame('80', $request->getServerParam('SERVER_PORT'));
        $this->assertNull($request->getServerParam('HTTPS'));
        $this->assertSame('off', $request->getServerParam('HTTPS', 'off'));
    }

    public function testWithAttribute(): void
    {
        $request = new Request('GET', '/');
        $this->assertNull($request->getAttribute('user'));

        $newRequest = $request->withAttribute('user', ['id' => 1, 'name' => 'John']);
        $this->assertSame(['id' => 1, 'name' => 'John'], $newRequest->getAttribute('user'));
        $this->assertNull($request->getAttribute('user')); // Проверяем иммутабельность
    }

    public function testGetAttribute(): void
    {
        $request = new Request('GET', '/');
        $request = $request->withAttribute('role', 'admin');
        $request = $request->withAttribute('permissions', ['read', 'write']);

        $this->assertSame('admin', $request->getAttribute('role'));
        $this->assertSame(['read', 'write'], $request->getAttribute('permissions'));
        $this->assertNull($request->getAttribute('missing'));
        $this->assertSame('default', $request->getAttribute('missing', 'default'));
    }

    public function testWithParams(): void
    {
        $request = new Request('GET', '/users');
        $this->assertSame([], $request->getParams());

        $newRequest = $request->withParams(['id' => '123']);
        $this->assertSame(['id' => '123'], $newRequest->getParams());
        $this->assertSame([], $request->getParams()); // Проверяем иммутабельность
    }

    public function testWithUri(): void
    {
        $request = new Request('GET', '/old');
        $this->assertSame('/old', $request->getUri());

        $newRequest = $request->withUri('/new');
        $this->assertSame('/new', $newRequest->getUri());
        $this->assertSame('/old', $request->getUri()); // Проверяем иммутабельность
    }

    public function testGetPath(): void
    {
        $request1 = new Request('GET', '/users/123');
        $this->assertSame('/users/123', $request1->getPath());

        $request2 = new Request('GET', '/users/123?page=1&limit=10');
        $this->assertSame('/users/123', $request2->getPath());

        $request3 = new Request('GET', '/');
        $this->assertSame('/', $request3->getPath());
    }

    public function testIsSecure(): void
    {
        $requestHttp = new Request('GET', '/', [], [], [], []);
        $this->assertFalse($requestHttp->isSecure());

        $requestHttps1 = new Request('GET', '/', [], [], [], ['HTTPS' => 'on']);
        $this->assertTrue($requestHttps1->isSecure());

        $requestHttps2 = new Request('GET', '/', [], [], [], ['SERVER_PORT' => '443']);
        $this->assertTrue($requestHttps2->isSecure());

        $requestHttps3 = new Request('GET', '/', [], [], [], ['HTTP_X_FORWARDED_PROTO' => 'https']);
        $this->assertTrue($requestHttps3->isSecure());

        $requestHttps4 = new Request('GET', '/', [], [], [], ['HTTP_X_FORWARDED_SSL' => 'on']);
        $this->assertTrue($requestHttps4->isSecure());
    }

    public function testGetScheme(): void
    {
        $requestHttp = new Request('GET', '/', [], [], [], []);
        $this->assertSame('http', $requestHttp->getScheme());

        $requestHttps = new Request('GET', '/', [], [], [], ['HTTPS' => 'on']);
        $this->assertSame('https', $requestHttps->getScheme());
    }

    public function testGetHost(): void
    {
        $request1 = new Request('GET', '/', [], [], ['Host' => 'example.com']);
        $this->assertSame('example.com', $request1->getHost());

        $request2 = new Request('GET', '/', [], [], [], ['SERVER_NAME' => 'localhost']);
        $this->assertSame('localhost', $request2->getHost());

        $request3 = new Request('GET', '/');
        $this->assertSame('localhost', $request3->getHost());
    }

    public function testGetFullUrl(): void
    {
        $request = new Request(
            'GET',
            '/users/123?page=1',
            [],
            [],
            ['Host' => 'example.com'],
            ['HTTPS' => 'on']
        );

        $this->assertSame('https://example.com/users/123?page=1', $request->getFullUrl());
    }

    public function testGetClientIpWithoutProxy(): void
    {
        $request = new Request('GET', '/', [], [], [], ['REMOTE_ADDR' => '192.168.1.1']);
        $this->assertSame('192.168.1.1', $request->getClientIp(false));
    }

    public function testGetClientIpWithProxy(): void
    {
        $request = new Request('GET', '/', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.1, 10.0.0.1'
        ]);

        $this->assertSame('10.0.0.1', $request->getClientIp(false));
        $this->assertSame('203.0.113.1', $request->getClientIp(true));
    }

    public function testGetClientIpWithCloudflare(): void
    {
        $request = new Request('GET', '/', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_CF_CONNECTING_IP' => '203.0.113.1'
        ]);

        $this->assertSame('203.0.113.1', $request->getClientIp(true));
    }

    public function testGetClientIpDefault(): void
    {
        $request = new Request('GET', '/');
        $this->assertSame('0.0.0.0', $request->getClientIp());
    }

    public function testIsJson(): void
    {
        $request1 = new Request('POST', '/', [], [], ['Content-Type' => 'application/json']);
        $this->assertTrue($request1->isJson());

        $request2 = new Request('POST', '/', [], [], ['Content-Type' => 'application/json; charset=utf-8']);
        $this->assertTrue($request2->isJson());

        $request3 = new Request('POST', '/', [], [], ['Content-Type' => 'application/x-www-form-urlencoded']);
        $this->assertFalse($request3->isJson());

        $request4 = new Request('POST', '/');
        $this->assertFalse($request4->isJson());
    }

    public function testIsAjax(): void
    {
        $request1 = new Request('GET', '/', [], [], ['X-Requested-With' => 'XMLHttpRequest']);
        $this->assertTrue($request1->isAjax());

        $request2 = new Request('GET', '/', [], [], ['X-Requested-With' => 'xmlhttprequest']);
        $this->assertTrue($request2->isAjax());

        $request3 = new Request('GET', '/');
        $this->assertFalse($request3->isAjax());
    }

    public function testGetCookies(): void
    {
        $request = new Request('GET', '/');
        $this->assertSame([], $request->getCookies());
    }

    public function testGetCookie(): void
    {
        $request = new Request('GET', '/');
        $this->assertNull($request->getCookie('session'));
        $this->assertSame('default', $request->getCookie('session', 'default'));
    }

    public function testHasCookie(): void
    {
        $request = new Request('GET', '/');
        $this->assertFalse($request->hasCookie('session'));
    }

    public function testGetBody(): void
    {
        $request = new Request('POST', '/');
        $this->assertSame([], $request->getBody());
    }

    public function testInput(): void
    {
        $request = new Request('POST', '/');
        $this->assertNull($request->input('email'));
        $this->assertSame('default@example.com', $request->input('email', 'default@example.com'));
    }

    public function testHas(): void
    {
        $request = new Request('POST', '/');
        $this->assertFalse($request->has('email'));
    }

    public function testGetFiles(): void
    {
        $request = new Request('POST', '/');
        $this->assertSame([], $request->getFiles());
    }

    public function testFile(): void
    {
        $request = new Request('POST', '/');
        $this->assertNull($request->file('avatar'));
    }

    public function testHasFile(): void
    {
        $request = new Request('POST', '/');
        $this->assertFalse($request->hasFile('avatar'));
    }

    public function testImmutability(): void
    {
        $original = new Request('GET', '/original', ['id' => '1']);

        $modified = $original
            ->withUri('/modified')
            ->withParams(['id' => '2'])
            ->withAttribute('user', 'admin');

        $this->assertSame('/original', $original->getUri());
        $this->assertSame(['id' => '1'], $original->getParams());
        $this->assertNull($original->getAttribute('user'));

        $this->assertSame('/modified', $modified->getUri());
        $this->assertSame(['id' => '2'], $modified->getParams());
        $this->assertSame('admin', $modified->getAttribute('user'));
    }
}

