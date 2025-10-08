<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Http;

use FaustVik\Router\Http\Cookie;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для класса Cookie
 */
final class CookieTest extends TestCase
{
    public function testConstructorSetsDefaultValues(): void
    {
        $cookie = new Cookie('test_cookie', 'test_value');

        $this->assertSame('test_cookie', $cookie->getName());
        $this->assertSame('test_value', $cookie->getValue());
        $this->assertSame(0, $cookie->getExpires());
        $this->assertSame('/', $cookie->getPath());
        $this->assertSame('', $cookie->getDomain());
        $this->assertFalse($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('Lax', $cookie->getSameSite());
    }

    public function testConstructorWithAllParameters(): void
    {
        $cookie = new Cookie(
            'test_cookie',
            'test_value',
            3600,
            '/path',
            'example.com',
            true,
            false,
            'Strict'
        );

        $this->assertSame('test_cookie', $cookie->getName());
        $this->assertSame('test_value', $cookie->getValue());
        $this->assertSame(3600, $cookie->getExpires());
        $this->assertSame('/path', $cookie->getPath());
        $this->assertSame('example.com', $cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertSame('Strict', $cookie->getSameSite());
    }

    public function testCreateStaticMethod(): void
    {
        $cookie = Cookie::create('test_cookie', 'test_value', 3600);

        $this->assertSame('test_cookie', $cookie->getName());
        $this->assertSame('test_value', $cookie->getValue());
        $this->assertSame(3600, $cookie->getExpires());
    }

    public function testForgetStaticMethod(): void
    {
        $cookie = Cookie::forget('test_cookie');

        $this->assertSame('test_cookie', $cookie->getName());
        $this->assertSame('', $cookie->getValue());
        $this->assertSame(-3600, $cookie->getExpires());
    }

    public function testSecureMethod(): void
    {
        $cookie = Cookie::create('test_cookie', 'test_value');
        $this->assertFalse($cookie->isSecure());

        $secureCookie = $cookie->secure(true);
        $this->assertTrue($secureCookie->isSecure());
        $this->assertFalse($cookie->isSecure()); // Проверяем иммутабельность
    }

    public function testHttpOnlyMethod(): void
    {
        $cookie = Cookie::create('test_cookie', 'test_value');
        $this->assertTrue($cookie->isHttpOnly());

        $nonHttpOnlyCookie = $cookie->httpOnly(false);
        $this->assertFalse($nonHttpOnlyCookie->isHttpOnly());
        $this->assertTrue($cookie->isHttpOnly()); // Проверяем иммутабельность
    }

    public function testWithPathMethod(): void
    {
        $cookie = Cookie::create('test_cookie', 'test_value');
        $this->assertSame('/', $cookie->getPath());

        $newCookie = $cookie->withPath('/admin');
        $this->assertSame('/admin', $newCookie->getPath());
        $this->assertSame('/', $cookie->getPath()); // Проверяем иммутабельность
    }

    public function testWithDomainMethod(): void
    {
        $cookie = Cookie::create('test_cookie', 'test_value');
        $this->assertSame('', $cookie->getDomain());

        $newCookie = $cookie->withDomain('example.com');
        $this->assertSame('example.com', $newCookie->getDomain());
        $this->assertSame('', $cookie->getDomain()); // Проверяем иммутабельность
    }

    public function testWithExpiresMethod(): void
    {
        $cookie = Cookie::create('test_cookie', 'test_value');
        $this->assertSame(0, $cookie->getExpires());

        $newCookie = $cookie->withExpires(7200);
        $this->assertSame(7200, $newCookie->getExpires());
        $this->assertSame(0, $cookie->getExpires()); // Проверяем иммутабельность
    }

    public function testWithSameSiteMethod(): void
    {
        $cookie = Cookie::create('test_cookie', 'test_value');
        $this->assertSame('Lax', $cookie->getSameSite());

        $newCookie = $cookie->withSameSite('Strict');
        $this->assertSame('Strict', $newCookie->getSameSite());
        $this->assertSame('Lax', $cookie->getSameSite()); // Проверяем иммутабельность
    }

    public function testFluentInterface(): void
    {
        $cookie = Cookie::create('session', 'abc123')
            ->secure(true)
            ->httpOnly(true)
            ->withPath('/admin')
            ->withDomain('example.com')
            ->withExpires(3600)
            ->withSameSite('Strict');

        $this->assertSame('session', $cookie->getName());
        $this->assertSame('abc123', $cookie->getValue());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('/admin', $cookie->getPath());
        $this->assertSame('example.com', $cookie->getDomain());
        $this->assertSame(3600, $cookie->getExpires());
        $this->assertSame('Strict', $cookie->getSameSite());
    }

    public function testToArrayMethod(): void
    {
        $cookie = Cookie::create('test_cookie', 'test_value', 3600)
            ->secure(true)
            ->withPath('/test')
            ->withDomain('example.com')
            ->withSameSite('Strict');

        $expected = [
            'name' => 'test_cookie',
            'value' => 'test_value',
            'expires' => 3600,
            'path' => '/test',
            'domain' => 'example.com',
            'secure' => true,
            'httpOnly' => true,
            'sameSite' => 'Strict'
        ];

        $this->assertSame($expected, $cookie->toArray());
    }

    public function testImmutability(): void
    {
        $original = Cookie::create('test', 'value');

        $modified = $original
            ->secure(true)
            ->httpOnly(false)
            ->withPath('/new')
            ->withDomain('new.com')
            ->withExpires(1000)
            ->withSameSite('None');

        // Проверяем, что оригинальный cookie не изменился
        $this->assertSame('/', $original->getPath());
        $this->assertSame('', $original->getDomain());
        $this->assertSame(0, $original->getExpires());
        $this->assertFalse($original->isSecure());
        $this->assertTrue($original->isHttpOnly());
        $this->assertSame('Lax', $original->getSameSite());

        // Проверяем, что новый cookie имеет новые значения
        $this->assertSame('/new', $modified->getPath());
        $this->assertSame('new.com', $modified->getDomain());
        $this->assertSame(1000, $modified->getExpires());
        $this->assertTrue($modified->isSecure());
        $this->assertFalse($modified->isHttpOnly());
        $this->assertSame('None', $modified->getSameSite());
    }
}

