<?php

declare(strict_types=1);

namespace FaustVik\Router\Http;

/**
 * Класс для представления HTTP запроса
 *
 * Инкапсулирует данные HTTP запроса:
 * - HTTP метод (GET, POST, PUT, DELETE и т.д.)
 * - URI пути
 * - Параметры маршрута
 * - Query параметры
 * - HTTP заголовки
 * - Серверные переменные
 * - Атрибуты (для передачи данных через middleware)
 *
 * @package FaustVik\Router\Http
 */
final class Request
{
    private string $method;
    private string $uri;
    private array $params;
    private array $query;
    private array $headers;
    private array $server;
    private array $attributes = [];

    /**
     * Конструктор HTTP запроса
     */
    public function __construct(
        string $method = '',
        string $uri = '',
        array $params = [],
        array $query = [],
        array $headers = [],
        array $server = []
    ) {
        $this->method = $method;
        $this->uri = $uri;
        $this->params = $params;
        $this->query = $query;
        $this->headers = $headers;
        $this->server = $server;
    }
    /**
     * Создает запрос из глобальных переменных PHP
     *
     * Использует $_SERVER, $_GET и getallheaders() для создания объекта запроса
     */
    public static function createFromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $query = $_GET;
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $server = $_SERVER;

        return new self($method, $uri, [], $query, $headers, $server);
    }
    public function getMethod(): string
    {
        return $this->method;
    }
    public function getUri(): string
    {
        return $this->uri;
    }
    public function getParams(): array
    {
        return $this->params;
    }
    /**
     * Получает конкретный параметр маршрута
     *
     * @param string $key Ключ параметра
     * @param mixed $default Значение по умолчанию если параметр не найден
     * @return mixed Значение параметра или значение по умолчанию
     */
    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }
    public function getQuery(): array
    {
        return $this->query;
    }
    /**
     * Получает конкретный query параметр
     *
     * @param string $key Ключ параметра
     * @param mixed $default Значение по умолчанию если параметр не найден
     * @return mixed Значение параметра или значение по умолчанию
     */
    public function getQueryParam(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
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
    public function getServer(): array
    {
        return $this->server;
    }
    /**
     * Получает конкретную серверную переменную
     *
     * @param string $key Ключ переменной
     * @param mixed $default Значение по умолчанию если переменная не найдена
     * @return mixed Значение переменной или значение по умолчанию
     */
    public function getServerParam(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }
    /**
     * Получает атрибут запроса
     *
     * Атрибуты используются для передачи данных между middleware.
     * Например, аутентификация может сохранить пользователя в атрибуте.
     *
     * @param string $key Ключ атрибута
     * @param mixed $default Значение по умолчанию если атрибут не найден
     * @return mixed Значение атрибута или значение по умолчанию
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
    /**
     * Создает новый экземпляр запроса с добавленным атрибутом
     *
     * Используется immutable pattern - возвращает новый экземпляр,
     * не изменяя текущий.
     */
    public function withAttribute(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->attributes[$key] = $value;
        return $clone;
    }
    /**
     * Создает новый экземпляр запроса с измененными параметрами
     */
    public function withParams(array $params): self
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }
    /**
     * Создает новый экземпляр запроса с измененным URI
     */
    public function withUri(string $uri): self
    {
        $clone = clone $this;
        $clone->uri = $uri;
        return $clone;
    }
}
