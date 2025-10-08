<?php

declare(strict_types=1);

namespace FaustVik\Router\Http;

use FaustVik\Router\interfaces\Http\CookieInterface;

/**
 * Класс для работы с HTTP Cookie
 *
 * Представляет HTTP cookie с настройками безопасности и времени жизни.
 * Поддерживает fluent interface для удобной настройки.
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
     * Создает новый cookie
     *
     * @param string $name Имя cookie
     * @param string $value Значение cookie
     * @param int $expires Время жизни в секундах
     */
    public static function create(string $name, string $value = '', int $expires = 0): self
    {
        return new self($name, $value, $expires);
    }

    /**
     * Создает cookie для удаления (expires в прошлом)
     */
    public static function forget(string $name): self
    {
        return new self($name, '', -3600);
    }

    /**
     * Устанавливает флаг secure (только HTTPS)
     */
    public function secure(bool $secure = true): self
    {
        $clone = clone $this;
        $clone->secure = $secure;
        return $clone;
    }

    /**
     * Устанавливает флаг httpOnly (недоступна для JS)
     */
    public function httpOnly(bool $httpOnly = true): self
    {
        $clone = clone $this;
        $clone->httpOnly = $httpOnly;
        return $clone;
    }

    /**
     * Устанавливает путь действия cookie
     */
    public function withPath(string $path): self
    {
        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }

    /**
     * Устанавливает домен действия cookie
     */
    public function withDomain(string $domain): self
    {
        $clone = clone $this;
        $clone->domain = $domain;
        return $clone;
    }

    /**
     * Устанавливает время жизни
     */
    public function withExpires(int $expires): self
    {
        $clone = clone $this;
        $clone->expires = $expires;
        return $clone;
    }

    /**
     * Устанавливает SameSite политику
     *
     * @param 'Lax'|'Strict'|'None' $sameSite 'Strict', 'Lax', или 'None'
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
     * Отправляет cookie в браузер
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
     * Преобразует cookie в массив
     *
     * @return array<string, mixed>
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
