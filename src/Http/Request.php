<?php

declare(strict_types=1);

namespace FaustVik\Router\Http;

use FaustVik\Router\interfaces\Http\RequestInterface;

/**
 * Класс для представления HTTP запроса
 *
 * Инкапсулирует данные HTTP запроса:
 * - HTTP метод (GET, POST, PUT, DELETE и т.д.)
 * - URI пути
 * - Параметры маршрута
 * - Query параметры ($_GET)
 * - Body данные (JSON, form-data, для POST/PUT/PATCH/DELETE)
 * - Загруженные файлы ($_FILES)
 * - HTTP заголовки
 * - Серверные переменные
 * - Атрибуты (для передачи данных через middleware)
 *
 * Автоматически парсит JSON из php://input для Content-Type: application/json
 *
 * @package FaustVik\Router\Http
 */
final class Request implements RequestInterface
{
    private string $method;
    private string $uri;
    private array $params;
    private array $query;
    private array $headers;
    private array $server;
    private array $attributes = [];
    private array $body = [];      // Для POST/PUT данных
    private array $files = [];     // Для загруженных файлов
    private array $cookies = [];   // Для HTTP cookies

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
     * Использует $_SERVER, $_GET, $_POST, $_FILES, $_COOKIE и getallheaders() 
     * для создания объекта запроса. Автоматически парсит JSON body.
     * Поддерживает HTTP Method Override через _method поле или X-HTTP-Method-Override header.
     */
    public static function createFromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // HTTP Method Override для REST API через формы
        if ($method === 'POST') {
            // Из POST параметра _method
            if (isset($_POST['_method'])) {
                $method = strtoupper($_POST['_method']);
            }
            // Из HTTP заголовка X-HTTP-Method-Override
            elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
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
        if (str_contains(strtolower($contentType), 'application/json')) {
            $rawBody = file_get_contents('php://input');
            if ($rawBody !== false && $rawBody !== '') {
                try {
                    $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        $body = $decoded;
                    }
                } catch (\JsonException $e) {
                    // JSON невалиден - оставляем body пустым
                    // В production можно залогировать ошибку
                }
            }
        } 
        // Для POST запросов с form-data берем $_POST
        elseif ($method === 'POST' && !empty($_POST)) {
            $body = $_POST;
        }
        // Для PUT/PATCH/DELETE тоже пытаемся прочитать php://input
        elseif (in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
            $rawBody = file_get_contents('php://input');
            if ($rawBody !== false && $rawBody !== '') {
                // Пытаемся распарсить как JSON
                try {
                    $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        $body = $decoded;
                    }
                } catch (\JsonException $e) {
                    // Если не JSON, парсим как query string
                    parse_str($rawBody, $body);
                }
            }
        }
        
        $files = $_FILES;
        
        $request = new self($method, $uri, [], $query, $headers, $server);
        $request->body = $body;
        $request->files = $files;
        $request->cookies = $cookies;
        
        return $request;
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

    /**
     * Получает все данные из body запроса
     *
     * Для POST/PUT/PATCH/DELETE запросов возвращает данные из:
     * - JSON body (если Content-Type: application/json)
     * - $_POST (для form-data)
     * - php://input (для других типов)
     *
     * @return array Данные body запроса
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * Получает конкретное значение из body запроса
     *
     * Удобный метод для получения отдельных полей из body.
     * Пример: $email = $request->input('email', 'default@example.com');
     *
     * @param string $key Ключ поля
     * @param mixed $default Значение по умолчанию если поле не найдено
     * @return mixed Значение поля или значение по умолчанию
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * Проверяет наличие поля в body
     *
     * @param string $key Ключ поля
     * @return bool true если поле существует
     */
    public function has(string $key): bool
    {
        return isset($this->body[$key]);
    }

    /**
     * Получает все загруженные файлы
     *
     * @return array Массив $_FILES
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Получает конкретный загруженный файл
     *
     * @param string $key Ключ файла из формы
     * @return array|null Данные файла или null если файл не найден
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Проверяет наличие успешно загруженного файла
     *
     * @param string $key Ключ файла из формы
     * @return bool true если файл загружен без ошибок
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) 
            && is_array($this->files[$key]) 
            && ($this->files[$key]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    }

    /**
     * Проверяет является ли запрос JSON запросом
     *
     * @return bool true если Content-Type содержит application/json
     */
    public function isJson(): bool
    {
        $contentType = $this->getHeader('Content-Type', '');
        return str_contains(strtolower($contentType), 'application/json');
    }

    /**
     * Проверяет является ли запрос AJAX запросом
     *
     * @return bool true если запрос отправлен через XMLHttpRequest
     */
    public function isAjax(): bool
    {
        return strtolower($this->getHeader('X-Requested-With', '')) === 'xmlhttprequest';
    }

    /**
     * Получает все cookies
     *
     * @return array<string, string> Все HTTP cookies
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Получает конкретную cookie
     *
     * @param string $key Имя cookie
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getCookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Проверяет наличие cookie
     *
     * @param string $key Имя cookie
     * @return bool
     */
    public function hasCookie(string $key): bool
    {
        return isset($this->cookies[$key]);
    }

    /**
     * Проверяет использует ли запрос HTTPS
     *
     * Проверяет различные серверные переменные для определения HTTPS.
     * Учитывает прокси и балансировщики нагрузки.
     *
     * @return bool true если соединение защищено (HTTPS)
     */
    public function isSecure(): bool
    {
        // Стандартная проверка HTTPS
        if (isset($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') {
            return true;
        }

        // Проверка через порт
        if (isset($this->server['SERVER_PORT']) && (int)$this->server['SERVER_PORT'] === 443) {
            return true;
        }

        // Проверка через заголовки прокси
        if (isset($this->server['HTTP_X_FORWARDED_PROTO']) 
            && $this->server['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        if (isset($this->server['HTTP_X_FORWARDED_SSL']) 
            && $this->server['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }

        return false;
    }

    /**
     * Получает IP адрес клиента
     *
     * Определяет реальный IP адрес клиента с учетом прокси серверов
     * и балансировщиков нагрузки. Проверяет заголовки прокси в порядке приоритета.
     *
     * @param bool $trustProxy Доверять ли заголовкам прокси (по умолчанию false)
     * @return string IP адрес клиента
     */
    public function getClientIp(bool $trustProxy = false): string
    {
        // Если не доверяем прокси, возвращаем REMOTE_ADDR
        if (!$trustProxy) {
            return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        // Проверяем заголовки прокси в порядке приоритета
        $headers = [
            'HTTP_CF_CONNECTING_IP',    // Cloudflare
            'HTTP_X_REAL_IP',            // Nginx proxy
            'HTTP_X_FORWARDED_FOR',      // Стандартный прокси заголовок
            'HTTP_CLIENT_IP',            // Некоторые прокси
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
        ];

        foreach ($headers as $header) {
            if (!empty($this->server[$header])) {
                $ip = $this->server[$header];
                
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
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Получает путь URI без query string
     *
     * Пример: /users/123?page=1 -> /users/123
     *
     * @return string Путь без параметров
     */
    public function getPath(): string
    {
        $uri = $this->uri;
        $questionMarkPos = strpos($uri, '?');
        
        return $questionMarkPos !== false ? substr($uri, 0, $questionMarkPos) : $uri;
    }

    /**
     * Получает схему протокола
     *
     * @return string 'http' или 'https'
     */
    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Получает хост из заголовков
     *
     * @return string Имя хоста
     */
    public function getHost(): string
    {
        return $this->getHeader('Host', $this->server['SERVER_NAME'] ?? 'localhost');
    }

    /**
     * Получает полный URL запроса
     *
     * @return string Полный URL (scheme://host/path?query)
     */
    public function getFullUrl(): string
    {
        return $this->getScheme() . '://' . $this->getHost() . $this->uri;
    }
}
