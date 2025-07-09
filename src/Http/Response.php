<?php

declare(strict_types=1);

namespace FaustVik\Router\Http;

final class Response
{
    private string $content;
    private int $statusCode;
    private array $headers;

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public static function json(array $data, int $statusCode = 200, array $headers = []): self
    {
        $content = json_encode($data, JSON_THROW_ON_ERROR);
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        
        return new self($content, $statusCode, $headers);
    }

    public static function html(string $content, int $statusCode = 200, array $headers = []): self
    {
        $headers = array_merge(['Content-Type' => 'text/html'], $headers);
        
        return new self($content, $statusCode, $headers);
    }

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

    public function getHeader(string $key, mixed $default = null): mixed
    {
        return $this->headers[$key] ?? $default;
    }

    public function withContent(string $content): self
    {
        $clone = clone $this;
        $clone->content = $content;
        return $clone;
    }

    public function withStatusCode(int $statusCode): self
    {
        $clone = clone $this;
        $clone->statusCode = $statusCode;
        return $clone;
    }

    public function withHeader(string $key, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$key] = $value;
        return $clone;
    }

    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->headers = array_merge($clone->headers, $headers);
        return $clone;
    }

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