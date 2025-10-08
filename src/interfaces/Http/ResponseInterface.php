<?php

declare(strict_types=1);

namespace FaustVik\Router\interfaces\Http;

/**
 * Интерфейс для HTTP ответа
 *
 * Определяет контракт для работы с HTTP ответами.
 * Позволяет создавать альтернативные реализации и упрощает тестирование.
 * Использует iterable типы для большей гибкости с коллекциями.
 *
 * @package FaustVik\Router\interfaces\Http
 */
interface ResponseInterface
{
    /**
     * Создает новый экземпляр Response
     *
     * @param string $content Содержимое ответа
     * @param int $statusCode HTTP статус код
     * @param array<string, string> $headers HTTP заголовки
     * @return self
     */
    public static function create(string $content = '', int $statusCode = 200, iterable $headers = []): self;

    /**
     * Создает JSON ответ
     *
     * @param mixed $data Данные для сериализации в JSON
     * @param int $statusCode HTTP статус код
     * @param array<string, string> $headers Дополнительные HTTP заголовки
     * @return self
     */
    public static function json(mixed $data, int $statusCode = 200, iterable $headers = []): self;

    /**
     * Создает HTML ответ
     *
     * @param string $html HTML содержимое
     * @param int $statusCode HTTP статус код
     * @param array<string, string> $headers Дополнительные HTTP заголовки
     * @return self
     */
    public static function html(string $html, int $statusCode = 200, iterable $headers = []): self;

    /**
     * Создает redirect ответ
     *
     * @param string $url URL для перенаправления
     * @param int $statusCode HTTP статус код (301, 302, 307, 308)
     * @return self
     */
    public static function redirect(string $url, int $statusCode = 302): self;

    /**
     * Получает содержимое ответа
     *
     * @return string
     */
    public function getContent(): string;

    /**
     * Получает HTTP статус код
     *
     * @return int
     */
    public function getStatusCode(): int;

    /**
     * Получает все HTTP заголовки
     *
     * @return array<string, string> HTTP заголовки в формате ['Header-Name' => 'value']
     */
    public function getHeaders(): iterable;

    /**
     * Устанавливает содержимое ответа
     *
     * @param string $content Содержимое
     * @return self
     */
    public function setContent(string $content): self;

    /**
     * Устанавливает HTTP статус код
     *
     * @param int $statusCode Статус код
     * @return self
     */
    public function setStatusCode(int $statusCode): self;

    /**
     * Устанавливает HTTP заголовок
     *
     * @param string $name Название заголовка
     * @param string $value Значение заголовка
     * @return self
     */
    public function setHeader(string $name, string $value): self;

    /**
     * Создает новый экземпляр с измененным содержимым
     *
     * @param string $content Новое содержимое
     * @return self
     */
    public function withContent(string $content): self;

    /**
     * Создает новый экземпляр с измененным статус кодом
     *
     * @param int $statusCode Новый статус код
     * @return self
     */
    public function withStatusCode(int $statusCode): self;

    /**
     * Создает новый экземпляр с добавленным заголовком
     *
     * @param string $name Название заголовка
     * @param string $value Значение заголовка
     * @return self
     */
    public function withHeader(string $name, string $value): self;

    /**
     * Отправляет ответ клиенту
     *
     * Устанавливает заголовки и выводит содержимое
     *
     * @return void
     */
    public function send(): void;
}
