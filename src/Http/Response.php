<?php

declare(strict_types=1);

namespace FaustVik\Router\Http;

use FaustVik\Router\interfaces\Http\ResponseInterface;

/**
 * HTTP Response representation class
 *
 * Encapsulates HTTP response data:
 * - Response content
 * - HTTP status code
 * - HTTP headers
 *
 * Provides convenient methods for creating different response types:
 * - JSON responses
 * - HTML responses
 * - Redirects
 *
 * @package FaustVik\Router\Http
 */
final class Response implements ResponseInterface
{
    private string $content;
    private int $statusCode;
    /** @var array<string, mixed> */
    private array $headers;
    /** @var array<Cookie> */
    private array $cookies = [];

    /**
     * Конструктор HTTP ответа
     * 
     * @param array<string, mixed> $headers
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Creates new Response instance
     *
     * Static factory method for creating response.
     * Alternative to direct constructor call.
     *
     * @param string $content Response content
     * @param int $statusCode HTTP status code
     * @param array<string, string> $headers HTTP headers
     * @return self
     *
     * @example
     * $response = Response::create('Hello World!', 200);
     */
    public static function create(string $content = '', int $statusCode = 200, iterable $headers = []): self
    {
        $headersArray = is_array($headers) ? $headers : iterator_to_array($headers);
        return new self($content, $statusCode, $headersArray);
    }

    /**
     * Creates JSON response
     *
     * Automatically sets Content-Type: application/json
     * and encodes data to JSON format.
     *
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     * @param array<string, string> $headers Additional HTTP headers
     * @return self
     *
     * @example
     * return Response::json(['success' => true, 'data' => $users]);
     *
     * @example
     * return Response::json(['error' => 'Not found'], 404);
     */
    public static function json(mixed $data, int $statusCode = 200, iterable $headers = []): self
    {
        $headersArray = is_array($headers) ? $headers : iterator_to_array($headers);
        $content = json_encode($data, JSON_THROW_ON_ERROR);
        $headersArray = array_merge(['Content-Type' => 'application/json'], $headersArray);

        return new self($content, $statusCode, $headersArray);
    }

    /**
     * Creates HTML response
     *
     * Automatically sets Content-Type: text/html
     *
     * @param string $content HTML content
     * @param int $statusCode HTTP status code
     * @param array<string, string> $headers Additional HTTP headers
     * @return self
     *
     * @example
     * return Response::html('<h1>Hello World!</h1>');
     */
    public static function html(string $content, int $statusCode = 200, iterable $headers = []): self
    {
        $headersArray = is_array($headers) ? $headers : iterator_to_array($headers);
        $headersArray = array_merge(['Content-Type' => 'text/html'], $headersArray);

        return new self($content, $statusCode, $headersArray);
    }

    /**
     * Creates redirect response
     *
     * Sets Location header and appropriate status code
     *
     * @param string $url Target URL
     * @param int $statusCode HTTP status code (default 302)
     * @return self
     *
     * @example
     * return Response::redirect('/login');
     *
     * @example
     * return Response::redirect('https://example.com', 301);  // Permanent redirect
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    /**
     * Gets response content
     *
     * @return string Response content
     *
     * @example
     * $content = $response->getContent();
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Gets HTTP status code
     *
     * @return int Status code
     *
     * @example
     * if ($response->getStatusCode() === 200) {
     *     // Success
     * }
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Gets all HTTP headers
     *
     * @return array<string, string> All headers
     *
     * @example
     * $headers = $response->getHeaders();
     * foreach ($headers as $name => $value) {
     *     echo "$name: $value\n";
     * }
     */
    public function getHeaders(): array
    {
        $stringHeaders = [];
        foreach ($this->headers as $key => $value) {
            if (is_string($value)) {
                $stringHeaders[$key] = $value;
            } elseif (is_scalar($value)) {
                $stringHeaders[$key] = (string) $value;
            } else {
                $stringHeaders[$key] = '';
            }
        }
        return $stringHeaders;
    }

    /**
     * Sets response content (mutable)
     *
     * Modifies current instance.
     * For immutable variant use withContent().
     *
     * @param string $content Response content
     * @return self For fluent interface
     *
     * @example
     * $response->setContent('New content');
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Sets HTTP status code (mutable)
     *
     * Modifies current instance.
     * For immutable variant use withStatusCode().
     *
     * @param int $statusCode HTTP status code
     * @return self For fluent interface
     *
     * @example
     * $response->setStatusCode(404);
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Sets HTTP header (mutable)
     *
     * Modifies current instance.
     * For immutable variant use withHeader().
     *
     * @param string $name Header name
     * @param string $value Header value
     * @return self For fluent interface
     *
     * @example
     * $response->setHeader('X-Custom-Header', 'value');
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Gets specific HTTP header
     *
     * @param string $key Header name
     * @param mixed $default Default value if header not found
     * @return mixed Header value or default
     *
     * @example
     * $contentType = $response->getHeader('Content-Type');
     */
    public function getHeader(string $key, mixed $default = null): mixed
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Creates new response with changed content (immutable)
     *
     * Uses immutable pattern - returns new instance without modifying current one.
     *
     * @param string $content New content
     * @return self New response instance
     *
     * @example
     * $newResponse = $response->withContent('Updated content');
     */
    public function withContent(string $content): self
    {
        $clone = clone $this;
        $clone->content = $content;
        return $clone;
    }

    /**
     * Creates new response with changed status code (immutable)
     *
     * @param int $statusCode HTTP status code
     * @return self New response instance
     *
     * @example
     * $notFoundResponse = $response->withStatusCode(404);
     */
    public function withStatusCode(int $statusCode): self
    {
        $clone = clone $this;
        $clone->statusCode = $statusCode;
        return $clone;
    }

    /**
     * Creates new response with added header (immutable)
     *
     * @param string $key Header name
     * @param string $value Header value
     * @return self New response instance
     *
     * @example
     * $response = $response->withHeader('X-Custom', 'value');
     */
    public function withHeader(string $key, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$key] = $value;
        return $clone;
    }

    /**
     * Creates new response with added headers (immutable)
     *
     * @param array<string, mixed> $headers Headers to add
     * @return self New response instance
     *
     * @example
     * $response = $response->withHeaders([
     *     'X-Custom-1' => 'value1',
     *     'X-Custom-2' => 'value2'
     * ]);
     */
    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->headers = array_merge($clone->headers, $headers);
        return $clone;
    }

    /**
     * Sets cookie (simple way)
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param int|array<string, mixed> $options Lifetime in seconds or options array
     * @return self For fluent interface
     *
     * @example
     * // Simple cookie
     * $response->setCookie('theme', 'dark', 3600);
     *
     * @example
     * // With options
     * $response->setCookie('session_id', 'abc123', [
     *     'expires' => 3600,
     *     'path' => '/',
     *     'secure' => true,
     *     'httpOnly' => true
     * ]);
     */
    public function setCookie(string $name, string $value, int|array $options = 0): self
    {
        if (is_int($options)) {
            $cookie = new Cookie(name: $name, value: $value, expires: $options);
        } else {
            $expires = $options['expires'] ?? 0;
            $path = $options['path'] ?? '/';
            $domain = $options['domain'] ?? '';
            $secure = $options['secure'] ?? false;
            $httpOnly = $options['httpOnly'] ?? true;
            $sameSite = $options['sameSite'] ?? 'Lax';
            
            $cookie = new Cookie(
                name: $name,
                value: $value,
                expires: is_int($expires) ? $expires : 0,
                path: is_string($path) ? $path : '/',
                domain: is_string($domain) ? $domain : '',
                secure: is_bool($secure) ? $secure : false,
                httpOnly: is_bool($httpOnly) ? $httpOnly : true,
                sameSite: in_array($sameSite, ['Lax', 'Strict', 'None'], true) ? $sameSite : 'Lax'
            );
        }

        $this->cookies[$name] = $cookie;
        return $this;
    }

    /**
     * Adds cookie object (advanced way)
     *
     * @param Cookie $cookie Cookie object
     * @return self New response instance
     *
     * @example
     * $cookie = Cookie::create('theme', 'dark', 3600)->secure()->httpOnly();
     * $response = $response->withCookie($cookie);
     */
    public function withCookie(Cookie $cookie): self
    {
        $clone = clone $this;
        $clone->cookies[$cookie->getName()] = $cookie;
        return $clone;
    }

    /**
     * Deletes cookie
     *
     * @param string $name Cookie name to delete
     * @return self For fluent interface
     *
     * @example
     * $response->deleteCookie('session_id');
     */
    public function deleteCookie(string $name): self
    {
        $this->cookies[$name] = Cookie::forget($name);
        return $this;
    }

    /**
     * Gets all cookies
     *
     * @return array<string, Cookie> Array of cookie objects
     *
     * @example
     * $cookies = $response->getCookies();
     * foreach ($cookies as $cookie) {
     *     echo $cookie->getName();
     * }
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Sends response to client
     *
     * Sets HTTP status code, headers, cookies and outputs content.
     * Checks if headers haven't been sent yet.
     *
     * @example
     * $response = Response::json(['message' => 'Hello']);
     * $response->send();
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
