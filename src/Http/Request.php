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
    /** @var string HTTP метод (GET, POST, PUT, DELETE и т.д.) */
    private string $method;

    /** @var string URI пути запроса */
    private string $uri;

    /** @var array Параметры маршрута (извлеченные из URI) */
    private array $params;

    /** @var array Query параметры (из query string) */
    private array $query;

    /** @var array HTTP заголовки */
    private array $headers;

    /** @var array Серверные переменные ($_SERVER) */
    private array $server;

    /** @var array Атрибуты запроса (для передачи данных через middleware) */
    private array $attributes = [];

    /**
     * Конструктор HTTP запроса
     *
     * @param string $method HTTP метод (GET, POST, PUT, DELETE и т.д.)
     * @param string $uri URI пути запроса
     * @param array $params Параметры маршрута
     * @param array $query Query параметры
     * @param array $headers HTTP заголовки
     * @param array $server Серверные переменные
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
     *
     * @return self Экземпляр запроса
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

    /**
     * Получает HTTP метод запроса
     *
     * @return string HTTP метод (GET, POST, PUT, DELETE и т.д.)
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Получает URI запроса
     *
     * @return string URI пути запроса
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Получает все параметры маршрута
     *
     * @return array Параметры маршрута
     */
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

    /**
     * Получает все query параметры
     *
     * @return array Query параметры
     */
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

    /**
     * Получает все HTTP заголовки
     *
     * @return array HTTP заголовки
     */
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
     * Получает все серверные переменные
     *
     * @return array Серверные переменные
     */
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
     *
     * @param string $key Ключ атрибута
     * @param mixed $value Значение атрибута
     * @return self Новый экземпляр запроса с атрибутом
     */
    public function withAttribute(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->attributes[$key] = $value;
        return $clone;
    }

    /**
     * Создает новый экземпляр запроса с измененными параметрами
     *
     * @param array $params Новые параметры маршрута
     * @return self Новый экземпляр запроса с параметрами
     */
    public function withParams(array $params): self
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }

    /**
     * Создает новый экземпляр запроса с измененным URI
     *
     * @param string $uri Новый URI
     * @return self Новый экземпляр запроса с URI
     */
    public function withUri(string $uri): self
    {
        $clone = clone $this;
        $clone->uri = $uri;
        return $clone;
    }
}
