<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Http;

use FaustVik\Router\Http\Cookie;
use FaustVik\Router\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для класса Response
 */
final class ResponseTest extends TestCase
{
    public function testConstructorSetsDefaultValues(): void
    {
        $response = new Response();

        $this->assertSame('', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->getHeaders());
    }

    public function testConstructorWithParameters(): void
    {
        $response = new Response(
            'Hello World',
            404,
            ['Content-Type' => 'text/plain']
        );

        $this->assertSame('Hello World', $response->getContent());
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['Content-Type' => 'text/plain'], $response->getHeaders());
    }

    public function testCreateStaticMethod(): void
    {
        $response = Response::create('Test content', 201, ['X-Custom' => 'value']);

        $this->assertSame('Test content', $response->getContent());
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(['X-Custom' => 'value'], $response->getHeaders());
    }

    public function testJsonStaticMethod(): void
    {
        $data = ['name' => 'John', 'age' => 30];
        $response = Response::json($data, 200);

        $this->assertSame('{"name":"John","age":30}', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
    }

    public function testJsonWithCustomHeaders(): void
    {
        $data = ['status' => 'ok'];
        $response = Response::json($data, 201, ['X-Custom' => 'header']);

        $this->assertSame('{"status":"ok"}', $response->getContent());
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
        $this->assertSame('header', $response->getHeader('X-Custom'));
    }

    public function testHtmlStaticMethod(): void
    {
        $html = '<h1>Hello World</h1>';
        $response = Response::html($html, 200);

        $this->assertSame($html, $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeader('Content-Type'));
    }

    public function testHtmlWithCustomHeaders(): void
    {
        $html = '<p>Test</p>';
        $response = Response::html($html, 201, ['X-Frame-Options' => 'DENY']);

        $this->assertSame($html, $response->getContent());
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeader('Content-Type'));
        $this->assertSame('DENY', $response->getHeader('X-Frame-Options'));
    }

    public function testRedirectStaticMethod(): void
    {
        $response = Response::redirect('/new-page');

        $this->assertSame('', $response->getContent());
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/new-page', $response->getHeader('Location'));
    }

    public function testRedirectWithCustomStatusCode(): void
    {
        $response = Response::redirect('/permanent', 301);

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('/permanent', $response->getHeader('Location'));
    }

    public function testSetContent(): void
    {
        $response = new Response('original');
        $this->assertSame('original', $response->getContent());

        $response->setContent('modified');
        $this->assertSame('modified', $response->getContent());
    }

    public function testSetContentReturnsInstance(): void
    {
        $response = new Response();
        $returned = $response->setContent('test');

        $this->assertSame($response, $returned);
    }

    public function testSetStatusCode(): void
    {
        $response = new Response('', 200);
        $this->assertSame(200, $response->getStatusCode());

        $response->setStatusCode(404);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testSetStatusCodeReturnsInstance(): void
    {
        $response = new Response();
        $returned = $response->setStatusCode(201);

        $this->assertSame($response, $returned);
    }

    public function testSetHeader(): void
    {
        $response = new Response();
        $this->assertNull($response->getHeader('X-Custom'));

        $response->setHeader('X-Custom', 'value');
        $this->assertSame('value', $response->getHeader('X-Custom'));
    }

    public function testSetHeaderReturnsInstance(): void
    {
        $response = new Response();
        $returned = $response->setHeader('X-Test', 'value');

        $this->assertSame($response, $returned);
    }

    public function testGetHeader(): void
    {
        $response = new Response('', 200, ['Content-Type' => 'application/json']);

        $this->assertSame('application/json', $response->getHeader('Content-Type'));
        $this->assertNull($response->getHeader('Missing'));
        $this->assertSame('default', $response->getHeader('Missing', 'default'));
    }

    public function testWithContent(): void
    {
        $original = new Response('original');
        $modified = $original->withContent('modified');

        $this->assertSame('original', $original->getContent());
        $this->assertSame('modified', $modified->getContent());
        $this->assertNotSame($original, $modified);
    }

    public function testWithStatusCode(): void
    {
        $original = new Response('', 200);
        $modified = $original->withStatusCode(404);

        $this->assertSame(200, $original->getStatusCode());
        $this->assertSame(404, $modified->getStatusCode());
        $this->assertNotSame($original, $modified);
    }

    public function testWithHeader(): void
    {
        $original = new Response();
        $modified = $original->withHeader('X-Custom', 'value');

        $this->assertNull($original->getHeader('X-Custom'));
        $this->assertSame('value', $modified->getHeader('X-Custom'));
        $this->assertNotSame($original, $modified);
    }

    public function testWithHeaders(): void
    {
        $original = new Response('', 200, ['X-Old' => 'old']);
        $modified = $original->withHeaders(['X-New' => 'new', 'X-Another' => 'another']);

        $this->assertSame(['X-Old' => 'old'], $original->getHeaders());
        $this->assertSame([
            'X-Old' => 'old',
            'X-New' => 'new',
            'X-Another' => 'another',
        ], $modified->getHeaders());
        $this->assertNotSame($original, $modified);
    }

    public function testSetCookieWithInteger(): void
    {
        $response = new Response();
        $response->setCookie('session', 'abc123', 3600);

        $cookies = $response->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertArrayHasKey('session', $cookies);
        $this->assertInstanceOf(Cookie::class, $cookies['session']);
        $this->assertSame('session', $cookies['session']->getName());
        $this->assertSame('abc123', $cookies['session']->getValue());
        $this->assertSame(3600, $cookies['session']->getExpires());
    }

    public function testSetCookieWithArray(): void
    {
        $response = new Response();
        $response->setCookie('test', 'value', [
            'expires' => 7200,
            'path' => '/admin',
            'domain' => 'example.com',
            'secure' => true,
            'httpOnly' => false,
            'sameSite' => 'Strict',
        ]);

        $cookies = $response->getCookies();
        $cookie = $cookies['test'];

        $this->assertSame('test', $cookie->getName());
        $this->assertSame('value', $cookie->getValue());
        $this->assertSame(7200, $cookie->getExpires());
        $this->assertSame('/admin', $cookie->getPath());
        $this->assertSame('example.com', $cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertSame('Strict', $cookie->getSameSite());
    }

    public function testWithCookie(): void
    {
        $cookie = Cookie::create('test', 'value', 3600);
        $original = new Response();
        $modified = $original->withCookie($cookie);

        $this->assertEmpty($original->getCookies());
        $this->assertCount(1, $modified->getCookies());
        $this->assertArrayHasKey('test', $modified->getCookies());
        $this->assertNotSame($original, $modified);
    }

    public function testDeleteCookie(): void
    {
        $response = new Response();
        $response->setCookie('session', 'abc123', 3600);
        $response->deleteCookie('session');

        $cookies = $response->getCookies();
        $cookie = $cookies['session'];

        $this->assertSame('session', $cookie->getName());
        $this->assertSame('', $cookie->getValue());
        $this->assertSame(-3600, $cookie->getExpires());
    }

    public function testGetCookies(): void
    {
        $response = new Response();
        $this->assertSame([], $response->getCookies());

        $cookie1 = Cookie::create('cookie1', 'value1');
        $cookie2 = Cookie::create('cookie2', 'value2');

        $response = $response->withCookie($cookie1)->withCookie($cookie2);

        $cookies = $response->getCookies();
        $this->assertCount(2, $cookies);
        $this->assertArrayHasKey('cookie1', $cookies);
        $this->assertArrayHasKey('cookie2', $cookies);
    }

    public function testFluentMutableInterface(): void
    {
        $response = new Response();
        $returned = $response
            ->setContent('Test')
            ->setStatusCode(201)
            ->setHeader('X-Custom', 'value');

        $this->assertSame($response, $returned);
        $this->assertSame('Test', $response->getContent());
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('value', $response->getHeader('X-Custom'));
    }

    public function testFluentImmutableInterface(): void
    {
        $original = new Response('original', 200);
        $modified = $original
            ->withContent('modified')
            ->withStatusCode(404)
            ->withHeader('X-Custom', 'value');

        $this->assertNotSame($original, $modified);
        $this->assertSame('original', $original->getContent());
        $this->assertSame(200, $original->getStatusCode());
        $this->assertNull($original->getHeader('X-Custom'));

        $this->assertSame('modified', $modified->getContent());
        $this->assertSame(404, $modified->getStatusCode());
        $this->assertSame('value', $modified->getHeader('X-Custom'));
    }

    public function testImmutability(): void
    {
        $original = new Response('original', 200, ['X-Old' => 'old']);
        $cookie = Cookie::create('test', 'value');

        $modified = $original
            ->withContent('new')
            ->withStatusCode(404)
            ->withHeader('X-New', 'new')
            ->withCookie($cookie);

        // Проверяем, что оригинальный не изменился
        $this->assertSame('original', $original->getContent());
        $this->assertSame(200, $original->getStatusCode());
        $this->assertSame(['X-Old' => 'old'], $original->getHeaders());
        $this->assertEmpty($original->getCookies());

        // Проверяем, что модифицированный имеет новые значения
        $this->assertSame('new', $modified->getContent());
        $this->assertSame(404, $modified->getStatusCode());
        $this->assertArrayHasKey('X-New', $modified->getHeaders());
        $this->assertCount(1, $modified->getCookies());
    }
}
