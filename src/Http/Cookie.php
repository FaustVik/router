<?php

declare(strict_types=1);

namespace FaustVik\Router\Http;

use FaustVik\Router\interfaces\Http\CookieInterface;

/**
 * HTTP Cookie management class
 *
 * Represents HTTP cookie with security settings and lifetime.
 * Supports fluent interface for easy configuration.
 *
 * @package FaustVik\Router\Http
 */
final class Cookie implements CookieInterface
{
    private string $name;
    private string $value;
    private int $expires = 0;
    private string $path = '/';
    private string $domain = '';
    private bool $secure = false;
    private bool $httpOnly = true;
    /** @var 'Lax'|'Strict'|'None' */
    private string $sameSite = 'Lax';

    /**
     * @param 'Lax'|'Strict'|'None' $sameSite
     * @throws \InvalidArgumentException If sameSite value is invalid
     */
    public function __construct(
        string $name,
        string $value = '',
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ) {
        // Validate sameSite value
        if (!in_array($sameSite, ['Lax', 'Strict', 'None'], true)) {
            throw new \InvalidArgumentException("Invalid sameSite value. Must be 'Lax', 'Strict', or 'None'");
        }

        $this->name = $name;
        $this->value = $value;
        $this->expires = $expires;
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        // Type already validated above, PHPStan can't track validation
        assert(in_array($sameSite, ['Lax', 'Strict', 'None'], true));
        $this->sameSite = $sameSite;
    }

    /**
     * Creates new cookie
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param int $expires Lifetime in seconds
     * @return self
     *
     * @example
     * $cookie = Cookie::create('theme', 'dark', 3600);
     */
    public static function create(string $name, string $value = '', int $expires = 0): self
    {
        return new self($name, $value, $expires);
    }

    /**
     * Creates cookie for deletion (expires in the past)
     *
     * @param string $name Cookie name to delete
     * @return self
     *
     * @example
     * $cookie = Cookie::forget('session_id');
     * $response->withCookie($cookie);
     */
    public static function forget(string $name): self
    {
        return new self($name, '', -3600);
    }

    /**
     * Sets secure flag (HTTPS only)
     *
     * @param bool $secure Secure flag value
     * @return self New cookie instance
     *
     * @example
     * $cookie = Cookie::create('session', '123')->secure();
     */
    public function secure(bool $secure = true): self
    {
        $clone = clone $this;
        $clone->secure = $secure;
        return $clone;
    }

    /**
     * Sets httpOnly flag (inaccessible from JS)
     *
     * @param bool $httpOnly HttpOnly flag value
     * @return self New cookie instance
     *
     * @example
     * $cookie = Cookie::create('session', '123')->httpOnly();
     */
    public function httpOnly(bool $httpOnly = true): self
    {
        $clone = clone $this;
        $clone->httpOnly = $httpOnly;
        return $clone;
    }

    /**
     * Sets cookie path
     *
     * @param string $path Cookie path
     * @return self New cookie instance
     *
     * @example
     * $cookie = Cookie::create('theme', 'dark')->withPath('/admin');
     */
    public function withPath(string $path): self
    {
        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }

    /**
     * Sets cookie domain
     *
     * @param string $domain Cookie domain
     * @return self New cookie instance
     *
     * @example
     * $cookie = Cookie::create('lang', 'en')->withDomain('.example.com');
     */
    public function withDomain(string $domain): self
    {
        $clone = clone $this;
        $clone->domain = $domain;
        return $clone;
    }

    /**
     * Sets cookie lifetime
     *
     * @param int $expires Lifetime in seconds
     * @return self New cookie instance
     *
     * @example
     * $cookie = Cookie::create('session', '123')->withExpires(7200);
     */
    public function withExpires(int $expires): self
    {
        $clone = clone $this;
        $clone->expires = $expires;
        return $clone;
    }

    /**
     * Sets SameSite policy
     *
     * @param 'Lax'|'Strict'|'None' $sameSite 'Strict', 'Lax', or 'None'
     * @return self New cookie instance
     *
     * @example
     * $cookie = Cookie::create('csrf', 'token')->withSameSite('Strict');
     * @throws \InvalidArgumentException If sameSite value is invalid
     */
    public function withSameSite(string $sameSite): self
    {
        if (!in_array($sameSite, ['Lax', 'Strict', 'None'], true)) {
            throw new \InvalidArgumentException("Invalid sameSite value. Must be 'Lax', 'Strict', or 'None'");
        }

        $clone = clone $this;
        assert(in_array($sameSite, ['Lax', 'Strict', 'None'], true));
        $clone->sameSite = $sameSite;
        return $clone;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getExpires(): int
    {
        return $this->expires;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    public function getSameSite(): string
    {
        return $this->sameSite;
    }

    /**
     * Sends cookie to browser
     *
     * @return bool Success status
     *
     * @example
     * $cookie = Cookie::create('theme', 'dark', 3600);
     * $cookie->send();
     */
    public function send(): bool
    {
        $expires = $this->expires > 0 ? time() + $this->expires : 0;

        if (PHP_VERSION_ID >= 70300) {
            return setcookie($this->name, $this->value, [
                'expires' => $expires,
                'path' => $this->path,
                'domain' => $this->domain,
                'secure' => $this->secure,
                'httponly' => $this->httpOnly,
                'samesite' => $this->sameSite
            ]);
        }

        // Fallback для PHP < 7.3
        return setcookie(
            $this->name,
            $this->value,
            $expires,
            $this->path,
            $this->domain,
            $this->secure,
            $this->httpOnly
        );
    }

    /**
     * Converts cookie to array
     *
     * @return array<string, mixed> Cookie data as array
     *
     * @example
     * $cookie = Cookie::create('theme', 'dark');
     * $data = $cookie->toArray();
     * // ['name' => 'theme', 'value' => 'dark', ...]
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'expires' => $this->expires,
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httpOnly' => $this->httpOnly,
            'sameSite' => $this->sameSite
        ];
    }
}
