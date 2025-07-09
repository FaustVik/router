<?php

declare(strict_types=1);

namespace FaustVik\Router\Http;

final class Request
{
    private string $method;
    private string $uri;
    private array $params;
    private array $query;
    private array $headers;
    private array $server;
    private array $attributes = [];

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

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function getQueryParam(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $key, mixed $default = null): mixed
    {
        return $this->headers[$key] ?? $default;
    }

    public function getServer(): array
    {
        return $this->server;
    }

    public function getServerParam(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function withAttribute(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->attributes[$key] = $value;
        return $clone;
    }

    public function withParams(array $params): self
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }

    public function withUri(string $uri): self
    {
        $clone = clone $this;
        $clone->uri = $uri;
        return $clone;
    }
} 