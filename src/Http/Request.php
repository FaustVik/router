<?php

declare(strict_types=1);

namespace FaustVik\Router\Http;

use FaustVik\Router\Interfaces\Http\RequestInterface;
use JsonException;

/**
 * HTTP Request representation class
 *
 * Encapsulates HTTP request data:
 * - HTTP method (GET, POST, PUT, DELETE, etc.)
 * - URI path
 * - Route parameters
 * - Query parameters ($_GET)
 * - Body data (JSON, form-data, for POST/PUT/PATCH/DELETE)
 * - Uploaded files ($_FILES)
 * - HTTP headers
 * - Server variables
 * - Attributes (for passing data through middleware)
 *
 * Automatically parses JSON from php://input for Content-Type: application/json
 *
 * @package FaustVik\Router\Http
 */
final class Request implements RequestInterface
{
    private string $method;
    private string $uri;
    /** @var array<string, mixed> */
    private array $params;
    /** @var array<string, mixed> */
    private array $query;
    /** @var array<string, mixed> */
    private array $headers;
    /** @var array<string, mixed> */
    private array $server;
    /** @var array<string, mixed> */
    private array $attributes = [];
    /** @var array<string, mixed> */
    private array $body = [];
    // Для POST/PUT данных
    /** @var array<string, mixed> */
    private array $files = [];
    // Для загруженных файлов
    /** @var array<string, mixed> */
    private array $cookies = [];
    // Для HTTP cookies

    /**
     * Конструктор HTTP запроса
     *
     * @param array<string, mixed> $params
     * @param array<string, mixed> $query
     * @param array<string, mixed> $headers
     * @param array<string, mixed> $server
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
     * Creates request from PHP globals
     *
     * Uses $_SERVER, $_GET, $_POST, $_FILES, $_COOKIE and getallheaders()
     * to create request object. Automatically parses JSON body.
     * Supports HTTP Method Override via _method field or X-HTTP-Method-Override header.
     *
     * @return self
     *
     * @example
     * // In your public/index.php
     * $request = Request::createFromGlobals();
     * $router->handle($request);
     */
    public static function createFromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // HTTP Method Override для REST API через формы
        if ($method === 'POST') {
            // Из POST параметра _method
            if (isset($_POST['_method']) && is_string($_POST['_method'])) {
                $method = strtoupper($_POST['_method']);
            } elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                // Из HTTP заголовка X-HTTP-Method-Override
                $header = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
                if (is_string($header)) {
                    $method = strtoupper($header);
                }
            }
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $query = $_GET;
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $server = $_SERVER;
        $cookies = $_COOKIE;

        // Обработка POST/PUT данных
        $body = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        // Если Content-Type содержит application/json - парсим JSON из php://input
        if (is_string($contentType) && str_contains(strtolower($contentType), 'application/json')) {
            $rawBody = file_get_contents('php://input');
            if ($rawBody !== false && $rawBody !== '') {
                try {
                    $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        $body = $decoded;
                    }
                } catch (JsonException $e) {
                    // JSON невалиден - оставляем body пустым
                    // В production можно залогировать ошибку
                }
            }
        } elseif ($method === 'POST' && !empty($_POST)) {
            // Для POST запросов с form-data берем $_POST
            $body = $_POST;
        } elseif (in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
            // Для PUT/PATCH/DELETE тоже пытаемся прочитать php://input
            $rawBody = file_get_contents('php://input');
            if ($rawBody !== false && $rawBody !== '') {
                // Пытаемся распарсить как JSON
                try {
                    $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        $body = $decoded;
                    }
                } catch (JsonException $e) {
                    // Если не JSON, парсим как query string
                    parse_str($rawBody, $body);
                }
            }
        }

        $files = $_FILES;

        $request = new self(
            is_string($method) ? $method : 'GET',
            is_string($uri) ? $uri : '/',
            [],
            $query,
            $headers,
            $server
        );
        $request->body = $body;
        $request->files = $files;
        $request->cookies = $cookies;

        return $request;
    }

    /**
     * Gets HTTP method
     *
     * @return string HTTP method (GET, POST, PUT, DELETE, etc.)
     *
     * @example
     * if ($request->getMethod() === 'POST') {
     *     // Handle POST request
     * }
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Gets request URI
     *
     * @return string Full URI with query string
     *
     * @example
     * echo $request->getUri();  // /users/123?page=1
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Gets all route parameters
     *
     * @return array<string, mixed> All parameters from route matching
     *
     * @example
     * // Route: /users/{id}/posts/{postId}
     * $params = $request->getParams();
     * // ['id' => '123', 'postId' => '456']
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Gets specific route parameter
     *
     * @param string $key Parameter key
     * @param mixed $default Default value if parameter not found
     * @return mixed Parameter value or default
     *
     * @example
     * $userId = $request->getParam('id', 0);
     * $username = $request->getParam('username', 'guest');
     */
    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Gets all query parameters
     *
     * @return array<string, mixed> All query parameters from URL
     *
     * @example
     * // URL: /users?page=2&sort=name
     * $query = $request->getQuery();
     * // ['page' => '2', 'sort' => 'name']
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * Gets specific query parameter
     *
     * @param string $key Parameter key
     * @param mixed $default Default value if parameter not found
     * @return mixed Parameter value or default
     *
     * @example
     * $page = $request->getQueryParam('page', 1);
     * $sort = $request->getQueryParam('sort', 'id');
     */
    public function getQueryParam(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Gets all HTTP headers
     *
     * @return array<string, string> All HTTP headers
     *
     * @example
     * $headers = $request->getHeaders();
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
     * Gets specific HTTP header
     *
     * @param string $key Header name
     * @param mixed $default Default value if header not found
     * @return mixed Header value or default
     *
     * @example
     * $token = $request->getHeader('Authorization');
     * $contentType = $request->getHeader('Content-Type', 'text/html');
     */
    public function getHeader(string $key, mixed $default = null): mixed
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Gets all server variables
     *
     * @return array<string, mixed> $_SERVER array
     *
     * @example
     * $serverVars = $request->getServer();
     * $httpHost = $serverVars['HTTP_HOST'] ?? 'localhost';
     */
    public function getServer(): array
    {
        return $this->server;
    }

    /**
     * Gets specific server variable
     *
     * @param string $key Variable key
     * @param mixed $default Default value if variable not found
     * @return mixed Variable value or default
     *
     * @example
     * $serverName = $request->getServerParam('SERVER_NAME', 'localhost');
     * $remoteAddr = $request->getServerParam('REMOTE_ADDR');
     */
    public function getServerParam(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Gets request attribute
     *
     * Attributes are used to pass data between middleware.
     * For example, authentication can store user in an attribute.
     *
     * @param string $key Attribute key
     * @param mixed $default Default value if attribute not found
     * @return mixed Attribute value or default
     *
     * @example
     * // In middleware: $request = $request->withAttribute('user', $user);
     * // In controller: $user = $request->getAttribute('user');
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Creates new request instance with added attribute
     *
     * Uses immutable pattern - returns new instance without modifying current one.
     *
     * @param string $key Attribute key
     * @param mixed $value Attribute value
     * @return self New request instance
     *
     * @example
     * $request = $request->withAttribute('user', $authenticatedUser);
     * $request = $request->withAttribute('role', 'admin');
     */
    public function withAttribute(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->attributes[$key] = $value;
        return $clone;
    }

    /**
     * Creates new request instance with changed parameters
     *
     * @param array<string, mixed> $params Route parameters
     * @return self New request instance
     *
     * @example
     * $request = $request->withParams(['id' => '123', 'slug' => 'hello']);
     */
    public function withParams(array $params): self
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }

    /**
     * Creates new request instance with changed URI
     *
     * @param string $uri New URI
     * @return self New request instance
     *
     * @example
     * $request = $request->withUri('/new/path');
     */
    public function withUri(string $uri): self
    {
        $clone = clone $this;
        $clone->uri = $uri;
        return $clone;
    }

    /**
     * Creates new request instance with changed body
     *
     * Useful for testing and manipulating request data
     *
     * @param array<string, mixed> $body Body data
     * @return self New request instance
     *
     * @example
     * $request = $request->withBody(['name' => 'John', 'email' => 'john@example.com']);
     */
    public function withBody(array $body): self
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    /**
     * Gets all data from request body
     *
     * For POST/PUT/PATCH/DELETE requests returns data from:
     * - JSON body (if Content-Type: application/json)
     * - $_POST (for form-data)
     * - php://input (for other types)
     *
     * @return array<string, mixed> Body data
     *
     * @example
     * $data = $request->getBody();
     * // ['name' => 'John', 'email' => 'john@example.com']
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * Gets specific value from request body
     *
     * Convenient method for getting individual fields from body.
     *
     * @param string $key Field key
     * @param mixed $default Default value if field not found
     * @return mixed Field value or default
     *
     * @example
     * $email = $request->input('email', 'default@example.com');
     * $name = $request->input('name');
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * Checks if field exists in body
     *
     * @param string $key Field key
     * @return bool true if field exists
     *
     * @example
     * if ($request->has('email')) {
     *     $email = $request->input('email');
     * }
     */
    public function has(string $key): bool
    {
        return isset($this->body[$key]);
    }

    /**
     * Gets all uploaded files
     *
     * @return array<string, mixed> $_FILES array
     *
     * @example
     * $files = $request->getFiles();
     * foreach ($files as $name => $file) {
     *     echo $file['name'];
     * }
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Gets specific uploaded file
     *
     * @param string $key File key from form
     * @return array<string, mixed>|null File data or null if not found
     *
     * @example
     * $avatar = $request->file('avatar');
     * if ($avatar) {
     *     move_uploaded_file($avatar['tmp_name'], '/path/to/avatars/' . $avatar['name']);
     * }
     */
    public function file(string $key): ?array
    {
        /** @var mixed $file */
        $file = $this->files[$key] ?? null;
        /** @var array<string, mixed>|null $result */
        $result = is_array($file) ? $file : null;
        return $result;
    }

    /**
     * Checks if file was successfully uploaded
     *
     * @param string $key File key from form
     * @return bool true if file uploaded without errors
     *
     * @example
     * if ($request->hasFile('avatar')) {
     *     $avatar = $request->file('avatar');
     *     // Process file upload
     * }
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key])
            && is_array($this->files[$key])
            && ($this->files[$key]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    }

    /**
     * Checks if request is JSON
     *
     * @return bool true if Content-Type contains application/json
     *
     * @example
     * if ($request->isJson()) {
     *     $data = $request->getBody();
     *     // Process JSON data
     * }
     */
    public function isJson(): bool
    {
        $contentType = $this->getHeader('Content-Type', '');
        $contentTypeStr = is_string($contentType) ? $contentType : '';
        return str_contains(strtolower($contentTypeStr), 'application/json');
    }

    /**
     * Checks if request is AJAX
     *
     * @return bool true if request sent via XMLHttpRequest
     *
     * @example
     * if ($request->isAjax()) {
     *     return Response::json($data);
     * }
     */
    public function isAjax(): bool
    {
        $header = $this->getHeader('X-Requested-With', '');
        $headerStr = is_string($header) ? $header : '';
        return strtolower($headerStr) === 'xmlhttprequest';
    }

    /**
     * Gets all cookies
     *
     * @return array<string, string> All HTTP cookies
     *
     * @example
     * $cookies = $request->getCookies();
     * foreach ($cookies as $name => $value) {
     *     echo "$name: $value\n";
     * }
     */
    public function getCookies(): array
    {
        $stringCookies = [];
        foreach ($this->cookies as $key => $value) {
            if (is_string($value)) {
                $stringCookies[$key] = $value;
            } elseif (is_scalar($value)) {
                $stringCookies[$key] = (string) $value;
            } else {
                $stringCookies[$key] = '';
            }
        }
        return $stringCookies;
    }

    /**
     * Gets specific cookie
     *
     * @param string $key Cookie name
     * @param mixed $default Default value
     * @return mixed Cookie value or default
     *
     * @example
     * $sessionId = $request->getCookie('session_id');
     * $theme = $request->getCookie('theme', 'light');
     */
    public function getCookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Checks if cookie exists
     *
     * @param string $key Cookie name
     * @return bool true if cookie exists
     *
     * @example
     * if ($request->hasCookie('session_id')) {
     *     $sessionId = $request->getCookie('session_id');
     * }
     */
    public function hasCookie(string $key): bool
    {
        return isset($this->cookies[$key]);
    }

    /**
     * Checks if request uses HTTPS
     *
     * Checks various server variables to determine HTTPS.
     * Takes into account proxies and load balancers.
     *
     * @return bool true if connection is secure (HTTPS)
     *
     * @example
     * if ($request->isSecure()) {
     *     // Handle HTTPS-specific logic
     * }
     */
    public function isSecure(): bool
    {
        // Стандартная проверка HTTPS
        if (isset($this->server['HTTPS'])) {
            $https = $this->server['HTTPS'];
            if (is_string($https) && $https !== 'off') {
                return true;
            }
            if (is_int($https) && $https !== 0) {
                return true;
            }
        }

        // Проверка через порт
        if (isset($this->server['SERVER_PORT'])) {
            $port = $this->server['SERVER_PORT'];
            if (is_int($port) && $port === 443) {
                return true;
            }
            if (is_string($port) && (int) $port === 443) {
                return true;
            }
        }

        // Проверка через заголовки прокси
        if (
            isset($this->server['HTTP_X_FORWARDED_PROTO'])
            && $this->server['HTTP_X_FORWARDED_PROTO'] === 'https'
        ) {
            return true;
        }

        if (
            isset($this->server['HTTP_X_FORWARDED_SSL'])
            && $this->server['HTTP_X_FORWARDED_SSL'] === 'on'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Gets client IP address
     *
     * Determines real client IP address considering proxy servers
     * and load balancers. Checks proxy headers in priority order.
     *
     * @param bool $trustProxy Whether to trust proxy headers (default false)
     * @return string Client IP address
     *
     * @example
     * // Basic usage
     * $ip = $request->getClientIp();
     *
     * @example
     * // Behind proxy (Cloudflare, nginx, etc.)
     * $ip = $request->getClientIp(trustProxy: true);
     */
    public function getClientIp(bool $trustProxy = false): string
    {
        // Если не доверяем прокси, возвращаем REMOTE_ADDR
        if (!$trustProxy) {
            $remoteAddr = $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
            return is_string($remoteAddr) ? $remoteAddr : '0.0.0.0';
        }

        // Проверяем заголовки прокси в порядке приоритета
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            // Cloudflare
            'HTTP_X_REAL_IP',
            // Nginx proxy
            'HTTP_X_FORWARDED_FOR',
            // Стандартный прокси заголовок
            'HTTP_CLIENT_IP',
            // Некоторые прокси
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
        ];

        foreach ($headers as $header) {
            if (!empty($this->server[$header])) {
                $ip = $this->server[$header];

                if (!is_string($ip)) {
                    continue;
                }

                // X-Forwarded-For может содержать несколько IP через запятую
                // Берем первый (клиентский)
                if (str_contains($ip, ',')) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                // Валидация IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Fallback на REMOTE_ADDR
        $remoteAddr = $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
        return is_string($remoteAddr) ? $remoteAddr : '0.0.0.0';
    }

    /**
     * Gets URI path without query string
     *
     * @return string Path without parameters
     *
     * @example
     * // URI: /users/123?page=1
     * $path = $request->getPath();  // /users/123
     */
    public function getPath(): string
    {
        $uri = $this->uri;
        $questionMarkPos = strpos($uri, '?');

        return $questionMarkPos !== false ? substr($uri, 0, $questionMarkPos) : $uri;
    }

    /**
     * Gets protocol scheme
     *
     * @return string 'http' or 'https'
     *
     * @example
     * $scheme = $request->getScheme();  // 'https'
     */
    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Gets host from headers
     *
     * @return string Host name
     *
     * @example
     * $host = $request->getHost();  // 'example.com'
     */
    public function getHost(): string
    {
        $serverName = $this->server['SERVER_NAME'] ?? 'localhost';
        $defaultHost = is_string($serverName) ? $serverName : 'localhost';
        $host = $this->getHeader('Host', $defaultHost);
        return is_string($host) ? $host : $defaultHost;
    }

    /**
     * Gets full request URL
     *
     * @return string Full URL (scheme://host/path?query)
     *
     * @example
     * $fullUrl = $request->getFullUrl();
     * // https://example.com/users/123?page=1
     */
    public function getFullUrl(): string
    {
        return $this->getScheme() . '://' . $this->getHost() . $this->uri;
    }
}
