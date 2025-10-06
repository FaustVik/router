<?php

declare(strict_types=1);

namespace FaustVik\Router\interfaces\Http;

/**
 * Интерфейс для HTTP Cookie
 *
 * Определяет контракт для работы с HTTP cookies.
 * Поддерживает fluent interface для цепочки вызовов.
 *
 * @package FaustVik\Router\interfaces\Http
 */
interface CookieInterface
{
    /**
     * Создает новый cookie
     *
     * @param string $name Имя cookie
     * @param string $value Значение cookie
     * @param int $expires Время жизни в секундах
     */
    public static function create(string $name, string $value = '', int $expires = 0): self;

    /**
     * Создает cookie для удаления
     */
    public static function forget(string $name): self;

    /**
     * Устанавливает флаг secure
     */
    public function secure(bool $secure = true): self;

    /**
     * Устанавливает флаг httpOnly
     */
    public function httpOnly(bool $httpOnly = true): self;

    /**
     * Устанавливает путь
     */
    public function withPath(string $path): self;

    /**
     * Устанавливает домен
     */
    public function withDomain(string $domain): self;

    /**
     * Устанавливает время жизни
     */
    public function withExpires(int $expires): self;

    /**
     * Устанавливает SameSite политику
     */
    public function withSameSite(string $sameSite): self;

    public function getName(): string;

    public function getValue(): string;

    public function getExpires(): int;

    public function getPath(): string;

    public function getDomain(): string;

    public function isSecure(): bool;

    public function isHttpOnly(): bool;

    public function getSameSite(): string;

    /**
     * Отправляет cookie в браузер
     */
    public function send(): bool;

    /**
     * Преобразует в массив
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}

