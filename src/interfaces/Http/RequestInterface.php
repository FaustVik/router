<?php

declare(strict_types=1);

namespace FaustVik\Router\interfaces\Http;

/**
 * Интерфейс для HTTP запроса
 *
 * Определяет контракт для работы с HTTP запросами.
 * Позволяет создавать альтернативные реализации и упрощает тестирование.
 * Использует iterable типы для большей гибкости с коллекциями.
 *
 * @package FaustVik\Router\interfaces\Http
 */
interface RequestInterface
{
    /**
     * Создает запрос из глобальных переменных PHP
     *
     * @return self
     */
    public static function createFromGlobals(): self;

    /**
     * Получает HTTP метод запроса
     *
     * @return string GET, POST, PUT, DELETE, PATCH и т.д.
     */
    public function getMethod(): string;

    /**
     * Получает URI запроса
     *
     * @return string URI пути (например /users/123)
     */
    public function getUri(): string;

    /**
     * Получает все параметры маршрута
     *
     * @return array<string, mixed> Ассоциативный массив параметров из URL
     */
    public function getParams(): iterable;

    /**
     * Получает конкретный параметр маршрута
     *
     * @param string $key Ключ параметра
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getParam(string $key, mixed $default = null): mixed;

    /**
     * Получает все query параметры ($_GET)
     *
     * @return array<string, mixed> Query параметры из URL
     */
    public function getQuery(): iterable;

    /**
     * Получает конкретный query параметр
     *
     * @param string $key Ключ параметра
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getQueryParam(string $key, mixed $default = null): mixed;

    /**
     * Получает все данные из body запроса
     *
     * @return array<string, mixed> Данные из POST/PUT/PATCH/DELETE body
     */
    public function getBody(): iterable;

    /**
     * Получает конкретное значение из body запроса
     *
     * @param string $key Ключ поля
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed;

    /**
     * Проверяет наличие поля в body
     *
     * @param string $key Ключ поля
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Получает все HTTP заголовки
     *
     * @return array<string, string> HTTP заголовки в формате ['Header-Name' => 'value']
     */
    public function getHeaders(): iterable;

    /**
     * Получает конкретный HTTP заголовок
     *
     * @param string $key Название заголовка
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getHeader(string $key, mixed $default = null): mixed;

    /**
     * Получает все серверные переменные
     *
     * @return array<string, mixed> Серверные переменные $_SERVER
     */
    public function getServer(): iterable;

    /**
     * Получает конкретную серверную переменную
     *
     * @param string $key Ключ переменной
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getServerParam(string $key, mixed $default = null): mixed;

    /**
     * Получает все загруженные файлы
     *
     * @return array<string, array<string, mixed>> Загруженные файлы из $_FILES
     */
    public function getFiles(): iterable;

    /**
     * Получает конкретный загруженный файл
     *
     * @param string $key Ключ файла
     * @return array<string, mixed>|null Данные файла ['name', 'type', 'tmp_name', 'error', 'size']
     */
    public function file(string $key): ?array;

    /**
     * Проверяет наличие успешно загруженного файла
     *
     * @param string $key Ключ файла
     * @return bool
     */
    public function hasFile(string $key): bool;

    /**
     * Получает атрибут запроса
     *
     * @param string $key Ключ атрибута
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getAttribute(string $key, mixed $default = null): mixed;

    /**
     * Создает новый экземпляр запроса с добавленным атрибутом
     *
     * @param string $key Ключ атрибута
     * @param mixed $value Значение атрибута
     * @return self
     */
    public function withAttribute(string $key, mixed $value): self;

    /**
     * Создает новый экземпляр запроса с измененными параметрами
     *
     * @param array $params Новые параметры
     * @return self
     */
    public function withParams(array $params): self;

    /**
     * Создает новый экземпляр запроса с измененным URI
     *
     * @param string $uri Новый URI
     * @return self
     */
    public function withUri(string $uri): self;

    /**
     * Проверяет является ли запрос JSON запросом
     *
     * @return bool
     */
    public function isJson(): bool;

    /**
     * Проверяет является ли запрос AJAX запросом
     *
     * @return bool
     */
    public function isAjax(): bool;
}
