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
    /** @var string Содержимое ответа */
    private string $content;
    
    /** @var int HTTP статус код */
    private int $statusCode;
    
    /** @var array HTTP заголовки */
    private array $headers;

    /**
     * Конструктор HTTP ответа
     * 
     * @param string $content Содержимое ответа
     * @param int $statusCode HTTP статус код (по умолчанию 200)
     * @param array $headers HTTP заголовки
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
     * 
     * @param array $data Данные для кодирования в JSON
     * @param int $statusCode HTTP статус код (по умолчанию 200)
     * @param array $headers Дополнительные HTTP заголовки
     * @return self Экземпляр Response с JSON данными
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
     * 
     * @param string $content HTML содержимое
     * @param int $statusCode HTTP статус код (по умолчанию 200)
     * @param array $headers Дополнительные HTTP заголовки
     * @return self Экземпляр Response с HTML содержимым
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
     * 
     * @param string $url URL для редиректа
     * @param int $statusCode HTTP статус код (по умолчанию 302)
     * @return self Экземпляр Response с редиректом
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    /**
     * Получает содержимое ответа
     * 
     * @return string Содержимое ответа
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Получает HTTP статус код
     * 
     * @return int HTTP статус код
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
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
     * Создает новый экземпляр ответа с измененным содержимым
     * 
     * Использует immutable pattern - возвращает новый экземпляр,
     * не изменяя текущий.
     * 
     * @param string $content Новое содержимое
     * @return self Новый экземпляр ответа
     */
    public function withContent(string $content): self
    {
        $clone = clone $this;
        $clone->content = $content;
        return $clone;
    }

    /**
     * Создает новый экземпляр ответа с измененным статус кодом
     * 
     * @param int $statusCode Новый HTTP статус код
     * @return self Новый экземпляр ответа
     */
    public function withStatusCode(int $statusCode): self
    {
        $clone = clone $this;
        $clone->statusCode = $statusCode;
        return $clone;
    }

    /**
     * Создает новый экземпляр ответа с добавленным заголовком
     * 
     * @param string $key Название заголовка
     * @param string $value Значение заголовка
     * @return self Новый экземпляр ответа
     */
    public function withHeader(string $key, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$key] = $value;
        return $clone;
    }

    /**
     * Создает новый экземпляр ответа с добавленными заголовками
     * 
     * @param array $headers Заголовки для добавления
     * @return self Новый экземпляр ответа
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
     * 
     * @return void
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