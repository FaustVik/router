<?php

declare(strict_types=1);

namespace FaustVik\Router\Http;

/**
 * Класс для представления HTTP ответа
 *
 * Инкапсулирует данные HTTP ответа:
 * - Содержимое ответа
 * - HTTP статус код
 * - HTTP заголовки
 *
 * Предоставляет удобные методы для создания различных типов ответов:
 * - JSON ответы
 * - HTML ответы
 * - Редиректы
 *
 * @package FaustVik\Router\Http
 */
final class Response
{
    private string $content;
    private int $statusCode;
    private array $headers;

    /**
     * Конструктор HTTP ответа
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Создает JSON ответ
     *
     * Автоматически устанавливает Content-Type: application/json
     * и кодирует данные в JSON формат.
     */
    public static function json(array $data, int $statusCode = 200, array $headers = []): self
    {
        $content = json_encode($data, JSON_THROW_ON_ERROR);
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);

        return new self($content, $statusCode, $headers);
    }

    /**
     * Создает HTML ответ
     *
     * Автоматически устанавливает Content-Type: text/html
     */
    public static function html(string $content, int $statusCode = 200, array $headers = []): self
    {
        $headers = array_merge(['Content-Type' => 'text/html'], $headers);

        return new self($content, $statusCode, $headers);
    }

    /**
     * Создает редирект
     *
     * Устанавливает заголовок Location и соответствующий статус код
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Получает конкретный HTTP заголовок
     *
     * @param string $key Название заголовка
     * @param mixed $default Значение по умолчанию если заголовок не найден
     * @return mixed Значение заголовка или значение по умолчанию
     */
    public function getHeader(string $key, mixed $default = null): mixed
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Создает новый экземпляр ответа с измененным содержимым
     *
     * Использует immutable pattern - возвращает новый экземпляр,
     * не изменяя текущий.
     */
    public function withContent(string $content): self
    {
        $clone = clone $this;
        $clone->content = $content;
        return $clone;
    }

    /**
     * Создает новый экземпляр ответа с измененным статус кодом
     */
    public function withStatusCode(int $statusCode): self
    {
        $clone = clone $this;
        $clone->statusCode = $statusCode;
        return $clone;
    }

    /**
     * Создает новый экземпляр ответа с добавленным заголовком
     */
    public function withHeader(string $key, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$key] = $value;
        return $clone;
    }

    /**
     * Создает новый экземпляр ответа с добавленными заголовками
     */
    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->headers = array_merge($clone->headers, $headers);
        return $clone;
    }

    /**
     * Отправляет ответ клиенту
     *
     * Устанавливает HTTP статус код, заголовки и выводит содержимое.
     * Проверяет, что заголовки еще не были отправлены.
     */
    public function send(): void
    {
        // Установка HTTP кода ответа
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            // Установка заголовков
            foreach ($this->headers as $key => $value) {
                header($key . ': ' . $value);
            }
        }

        // Вывод содержимого
        echo $this->content;
    }
}
