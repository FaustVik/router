<?php

declare(strict_types=1);

namespace FaustVik\Router\Http;

use FaustVik\Router\interfaces\Http\ResponseInterface;

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
final class Response implements ResponseInterface
{
    private string $content;
    private int $statusCode;
    private array $headers;
    /** @var array<Cookie> */
    private array $cookies = [];

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
     * Создает новый экземпляр Response
     *
     * Статический фабричный метод для создания ответа.
     * Альтернатива прямому вызову конструктора.
     *
     * @param array<string, string> $headers HTTP заголовки
     */
    public static function create(string $content = '', int $statusCode = 200, iterable $headers = []): self
    {
        $headersArray = is_array($headers) ? $headers : iterator_to_array($headers);
        return new self($content, $statusCode, $headersArray);
    }

    /**
     * Создает JSON ответ
     *
     * Автоматически устанавливает Content-Type: application/json
     * и кодирует данные в JSON формат.
     *
     * @param array<string, string> $headers Дополнительные HTTP заголовки
     */
    public static function json(mixed $data, int $statusCode = 200, iterable $headers = []): self
    {
        $headersArray = is_array($headers) ? $headers : iterator_to_array($headers);
        $content = json_encode($data, JSON_THROW_ON_ERROR);
        $headersArray = array_merge(['Content-Type' => 'application/json'], $headersArray);

        return new self($content, $statusCode, $headersArray);
    }

    /**
     * Создает HTML ответ
     *
     * Автоматически устанавливает Content-Type: text/html
     *
     * @param array<string, string> $headers Дополнительные HTTP заголовки
     */
    public static function html(string $content, int $statusCode = 200, iterable $headers = []): self
    {
        $headersArray = is_array($headers) ? $headers : iterator_to_array($headers);
        $headersArray = array_merge(['Content-Type' => 'text/html'], $headersArray);

        return new self($content, $statusCode, $headersArray);
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
     * Устанавливает содержимое ответа
     *
     * Изменяет содержимое текущего экземпляра (mutable).
     * Для immutable варианта используйте withContent().
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Устанавливает HTTP статус код
     *
     * Изменяет статус код текущего экземпляра (mutable).
     * Для immutable варианта используйте withStatusCode().
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Устанавливает HTTP заголовок
     *
     * Изменяет заголовки текущего экземпляра (mutable).
     * Для immutable варианта используйте withHeader().
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
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
     * Устанавливает cookie (простой способ)
     *
     * @param string $name Имя cookie
     * @param string $value Значение cookie
     * @param int|array<string, mixed> $options Время жизни в секундах или массив опций
     */
    public function setCookie(string $name, string $value, int|array $options = 0): self
    {
        if (is_int($options)) {
            $cookie = new Cookie(name: $name, value: $value, expires: $options);
        } else {
            $cookie = new Cookie(
                name: $name,
                value: $value,
                expires: $options['expires'] ?? 0,
                path: $options['path'] ?? '/',
                domain: $options['domain'] ?? '',
                secure: $options['secure'] ?? false,
                httpOnly: $options['httpOnly'] ?? true,
                sameSite: $options['sameSite'] ?? 'Lax'
            );
        }
        
        $this->cookies[$name] = $cookie;
        return $this;
    }

    /**
     * Добавляет cookie объект (продвинутый способ)
     *
     * @param Cookie $cookie Объект cookie
     */
    public function withCookie(Cookie $cookie): self
    {
        $clone = clone $this;
        $clone->cookies[$cookie->getName()] = $cookie;
        return $clone;
    }

    /**
     * Удаляет cookie
     *
     * @param string $name Имя cookie для удаления
     */
    public function deleteCookie(string $name): self
    {
        $this->cookies[$name] = Cookie::forget($name);
        return $this;
    }

    /**
     * Получает все cookies
     *
     * @return array<string, Cookie>
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Отправляет ответ клиенту
     *
     * Устанавливает HTTP статус код, заголовки, cookies и выводит содержимое.
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

            // Установка cookies
            foreach ($this->cookies as $cookie) {
                $cookie->send();
            }
        }

        // Вывод содержимого
        echo $this->content;
    }
}
