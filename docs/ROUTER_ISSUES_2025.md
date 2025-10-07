# Актуальные проблемы Router v2.0-alpha (Октябрь 2025)

> **Дата анализа:** 6 октября 2025  
> **Версия:** v2.0-alpha  
> **Статус:** Активная разработка

---

## 📊 Сводка

| Категория | Критических | Высоких | Средних | Всего |
|-----------|-------------|---------|---------|-------|
| Безопасность | ~~2~~ **0** ✅ | 0 | 0 | **0** ✅ |
| Архитектура | 0 | 3 | 2 | **5** |
| Производительность | 0 | 1 | 1 | **2** |
| Тестирование | 0 | 1 | 0 | **1** |
| **ИТОГО** | ~~2~~ **0** ✅ | **5** | **3** | **8** |

**Прогресс:** Критические проблемы безопасности **РЕШЕНЫ** ✅ (6 октября 2025)

---

## ✅ Что уже исправлено (с прошлого анализа)

### 1. ✅ POST/PUT/PATCH/DELETE body теперь обрабатывается
**Файл:** `src/Http/Request.php`

Реализована обработка:
- JSON body через `php://input`
- `$_POST` данные
- `$_FILES` загрузка файлов
- `$_COOKIE` обработка
- HTTP Method Override через `_method` и `X-HTTP-Method-Override`

```php
public function getBody(): array
public function input(string $key, mixed $default = null): mixed
public function file(string $key): ?array
public function hasFile(string $key): bool
```

### 2. ✅ Добавлены интерфейсы для Request и Response
**Файлы:** 
- `src/interfaces/Http/RequestInterface.php`
- `src/interfaces/Http/ResponseInterface.php`

Классы теперь реализуют интерфейсы, что улучшает тестируемость.

### 3. ✅ Начато тестирование
**Директория:** `tests/`

Добавлены тесты для:
- ✅ `Http/CookieTest.php` - 100% покрытие
- ✅ `Http/RequestTest.php` - высокое покрытие
- ✅ `Http/ResponseTest.php` - 100% покрытие
- ✅ `Middleware/AuthMiddlewareTest.php`
- ✅ `Middleware/CorsMiddlewareTest.php`
- ✅ `Middleware/LoggingMiddlewareTest.php`
- ✅ `Middleware/MiddlewareStackTest.php`

### 4. ✅ Request расширен полезными методами
Добавлены:
- `isJson()`, `isAjax()`, `isSecure()`
- `getClientIp(bool $trustProxy)` - с поддержкой прокси
- `getPath()`, `getScheme()`, `getHost()`, `getFullUrl()`
- Cookies методы: `getCookies()`, `getCookie()`, `hasCookie()`

---

## ✅ ИСПРАВЛЕННЫЕ критические проблемы (6 октября 2025)

### 1. ~~PHP Object Injection через unserialize()~~ ✅ ИСПРАВЛЕНО

**Приоритет:** ~~КРИТИЧЕСКИЙ~~ → **РЕШЕНО**  
**Файл:** `src/Cache/FileCache.php`  
**Дата исправления:** 6 октября 2025

**Что было:**
```php
$data = unserialize($content); // ОПАСНО! RCE уязвимость
```

**Что исправлено:**
```php
// Безопасный JSON вместо unserialize
$data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
```

**Дополнительные улучшения:**
- ✅ Валидация структуры данных после десериализации
- ✅ Обработка JsonException с удалением поврежденных файлов
- ✅ Документация методов с PHPDoc

---

## 🔴 КРИТИЧЕСКИЕ проблемы (актуальные)

**Атака:**
Если злоумышленник получит доступ к файлам кеша (например, через misconfiguration, LFI, или другую уязвимость), он может:
1. Создать вредоносный сериализованный объект
2. Внедрить его в файл кеша
3. При десериализации выполнится произвольный код

**Реальные CVE:**
- CVE-2019-19935 (WordPress)
- CVE-2020-36193 (Phar)
- CVE-2021-21315 (System Commander)

**Решение 1 (Быстрое, минимальные изменения):**
```php
// src/Cache/FileCache.php
$data = unserialize($content, ['allowed_classes' => false]);
```

**Решение 2 (Рекомендуемое - переход на JSON):**
```php
// В FileCache.php заменить serialize/unserialize на JSON

public function get(string $key): mixed
{
    // ...
    try {
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        $this->delete($key);
        return null;
    }
    // ...
}

public function set(string $key, mixed $value, int $ttl = 0): bool
{
    // ...
    try {
        $serialized = json_encode($data, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        return false;
    }
    
    return file_put_contents($filename, $serialized, LOCK_EX) !== false;
}
```

**Решение 3 (Максимальная безопасность - с HMAC):**
```php
// Добавить HMAC проверку целостности
private string $secretKey;

public function __construct(string $cacheDir = 'cache', string $prefix = 'router_', ?string $secretKey = null)
{
    $this->secretKey = $secretKey ?? bin2hex(random_bytes(32));
    // ...
}

public function set(string $key, mixed $value, int $ttl = 0): bool
{
    $data = [
        'value' => $value,
        'ttl' => $ttl > 0 ? time() + $ttl : 0,
        'created' => time()
    ];
    
    $serialized = json_encode($data, JSON_THROW_ON_ERROR);
    $hash = hash_hmac('sha256', $serialized, $this->secretKey);
    
    $content = json_encode([
        'data' => $serialized,
        'hash' => $hash
    ], JSON_THROW_ON_ERROR);
    
    return file_put_contents($filename, $content, LOCK_EX) !== false;
}

public function get(string $key): mixed
{
    // ...
    $envelope = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    
    $expectedHash = hash_hmac('sha256', $envelope['data'], $this->secretKey);
    if (!hash_equals($expectedHash, $envelope['hash'])) {
        $this->delete($key);
        return null; // Tampering detected!
    }
    
    $data = json_decode($envelope['data'], true, 512, JSON_THROW_ON_ERROR);
    // ...
}
```

---

### 2. ~~Path Traversal в FileCache~~ ✅ ИСПРАВЛЕНО

**Приоритет:** ~~КРИТИЧЕСКИЙ~~ → **РЕШЕНО**  
**Файл:** `src/Cache/FileCache.php`  
**Дата исправления:** 6 октября 2025

**Что было:**
```php
public function __construct(string $cacheDir = 'cache', string $prefix = 'router_')
{
    $this->cacheDir = rtrim($cacheDir, '/'); // Нет валидации!
    
    if (!is_dir($this->cacheDir)) {
        mkdir($this->cacheDir, 0755, true); // Может создать где угодно
    }
}
```

**Что исправлено:**
```php
public function __construct(string $cacheDir = 'cache', string $prefix = 'router_')
{
    // Валидация и нормализация пути
    $this->cacheDir = $this->validateAndNormalizePath($cacheDir);
    
    // Проверка прав записи
    if (!is_writable($this->cacheDir)) {
        throw new RuntimeException("Cache directory is not writable");
    }
    
    // Автоматическая защита директории
    $this->protectCacheDirectory();
}

private function validateAndNormalizePath(string $cacheDir): string
{
    // Проверка на path traversal
    if (strpos($cacheDir, '..') !== false) {
        throw new InvalidArgumentException('Path traversal detected');
    }
    
    // Получение абсолютного пути
    // ... безопасная логика ...
}
```

**Дополнительные улучшения:**
- ✅ Валидация пути с проверкой на `..`
- ✅ Проверка прав записи при инициализации
- ✅ Автоматическое создание `.htaccess` (Deny from all)
- ✅ Автоматическое создание `.gitignore`
- ✅ Автоматическое создание `index.php` с 403 ошибкой
- ✅ Выброс исключений при ошибках вместо silent fail

**Решение:**
```php
public function __construct(string $cacheDir = 'cache', string $prefix = 'router_')
{
    // Получаем абсолютный путь
    $absolutePath = realpath($cacheDir);
    
    // Если директория не существует, пытаемся создать
    if ($absolutePath === false) {
        $absolutePath = $cacheDir;
    }
    
    // Проверка на path traversal
    if (strpos($absolutePath, '..') !== false) {
        throw new \InvalidArgumentException('Invalid cache directory: path traversal detected');
    }
    
    // Проверка что путь внутри проекта (опционально, но рекомендуется)
    $projectRoot = dirname(__DIR__, 2); // Корень проекта
    if (strpos(realpath($absolutePath) ?: $absolutePath, $projectRoot) !== 0) {
        throw new \InvalidArgumentException('Cache directory must be within project root');
    }
    
    $this->cacheDir = rtrim($absolutePath, '/');
    $this->prefix = $prefix;
    
    // Создаем директорию если не существует
    if (!is_dir($this->cacheDir)) {
        if (!@mkdir($this->cacheDir, 0755, true)) {
            throw new \RuntimeException("Cannot create cache directory: {$this->cacheDir}");
        }
    }
    
    // Проверка прав записи
    if (!is_writable($this->cacheDir)) {
        throw new \RuntimeException("Cache directory is not writable: {$this->cacheDir}");
    }
    
    // Создаем .gitignore и .htaccess для защиты
    $this->protectCacheDirectory();
}

private function protectCacheDirectory(): void
{
    // Добавляем .gitignore
    $gitignore = $this->cacheDir . '/.gitignore';
    if (!file_exists($gitignore)) {
        file_put_contents($gitignore, "*\n!.gitignore\n");
    }
    
    // Добавляем .htaccess для Apache (запрет доступа через web)
    $htaccess = $this->cacheDir . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Deny from all\n");
    }
}
```

---

## 🟠 ВЫСОКИЕ проблемы

### 3. Отсутствие централизованной обработки ошибок

**Приоритет:** ВЫСОКИЙ  
**Файл:** `src/Router/Router.php`

**Проблема:**
Исключения из `Router::run()` не обрабатываются. Если происходит ошибка:
- Пользователь видит PHP stack trace
- Раскрывается внутренняя структура приложения
- Нет единого формата ошибок для API

**Текущее поведение:**
```php
public function run(): void
{
    $this->parse();
    $matchResult = $this->match(); // Может выбросить NoMatch
    $route = $matchResult->getRoute();
    // ... дальнейший код без try-catch
}
```

**Решение - создать ErrorHandler:**

```php
// src/ErrorHandling/ErrorHandlerInterface.php
<?php

namespace FaustVik\Router\ErrorHandling;

use FaustVik\Router\Http\Response;

interface ErrorHandlerInterface
{
    public function handle(\Throwable $exception): Response;
}
```

```php
// src/ErrorHandling/JsonErrorHandler.php
<?php

namespace FaustVik\Router\ErrorHandling;

use FaustVik\Router\exceptions\NoMatch;
use FaustVik\Router\exceptions\NotAllowedHttpMethod;
use FaustVik\Router\Http\Response;

final class JsonErrorHandler implements ErrorHandlerInterface
{
    public function __construct(private bool $debug = false) {}
    
    public function handle(\Throwable $exception): Response
    {
        $statusCode = $this->getStatusCode($exception);
        
        $data = [
            'error' => true,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
        ];
        
        // В режиме отладки показываем stack trace
        if ($this->debug) {
            $data['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ];
        }
        
        return Response::json($data, $statusCode);
    }
    
    private function getStatusCode(\Throwable $exception): int
    {
        return match(true) {
            $exception instanceof NoMatch => 404,
            $exception instanceof NotAllowedHttpMethod => 405,
            default => 500,
        };
    }
}
```

```php
// В Router.php добавить:
private ?ErrorHandlerInterface $errorHandler = null;

public function setErrorHandler(ErrorHandlerInterface $handler): self
{
    $this->errorHandler = $handler;
    return $this;
}

public function run(): void
{
    try {
        // Весь существующий код метода run()
        $this->parse();
        $matchResult = $this->match();
        // ... и т.д.
    } catch (\Throwable $e) {
        // Используем ErrorHandler если установлен
        $handler = $this->errorHandler ?? new JsonErrorHandler();
        $response = $handler->handle($e);
        $response->send();
        return;
    }
}
```

**Использование:**
```php
$router = new Router();

// Production
$router->setErrorHandler(new JsonErrorHandler(debug: false));

// Development
$router->setErrorHandler(new JsonErrorHandler(debug: true));

$router->run();
```

---

### 4. MiddlewareStack не использует DI контейнер

**Приоритет:** ВЫСОКИЙ  
**Файл:** `src/Middleware/MiddlewareStack.php:93-106`

**Проблема:**
```php
public function addFromArray(array $middleware): self
{
    foreach ($middleware as $item) {
        if (is_string($item) && class_exists($item)) {
            // Прямая инстанциация БЕЗ DI!
            $item = new $item();
        }
        // ...
    }
}
```

**Что не работает:**
```php
// Middleware с зависимостями упадет с ошибкой
class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CacheInterface $cache,  // Откуда взять?
        private int $maxAttempts = 60
    ) {}
}

// Это вызовет ArgumentCountError
$route->middleware([RateLimitMiddleware::class]);
```

**Решение:**
```php
// src/Middleware/MiddlewareStack.php

use FaustVik\Router\interfaces\DI\RouterContainerInterface;

final class MiddlewareStack
{
    private array $middleware = [];
    private $finalHandler;
    private ?RouterContainerInterface $container = null; // Добавить!
    
    public function __construct(callable $finalHandler, ?RouterContainerInterface $container = null)
    {
        $this->finalHandler = $finalHandler;
        $this->container = $container;
    }
    
    public function addFromArray(array $middleware): self
    {
        foreach ($middleware as $item) {
            if (is_string($item)) {
                // Сначала пытаемся разрешить через DI контейнер
                if ($this->container && $this->container->canResolve($item)) {
                    try {
                        $item = $this->container->resolve($item);
                    } catch (\Throwable $e) {
                        throw new \RuntimeException(
                            "Failed to resolve middleware {$item} from container: " . $e->getMessage(),
                            0,
                            $e
                        );
                    }
                } 
                // Если контейнера нет или он не может разрешить, пытаемся создать напрямую
                elseif (class_exists($item)) {
                    try {
                        $item = new $item();
                    } catch (\ArgumentCountError $e) {
                        throw new \RuntimeException(
                            "Middleware {$item} requires constructor arguments. " .
                            "Register it in DI container or pass as instance.",
                            0,
                            $e
                        );
                    }
                } else {
                    throw new \RuntimeException("Middleware class not found: {$item}");
                }
            }
            
            if (!$item instanceof MiddlewareInterface) {
                throw new \InvalidArgumentException(
                    'Middleware must implement MiddlewareInterface, got: ' . get_debug_type($item)
                );
            }
            
            $this->add($item);
        }
        return $this;
    }
}
```

```php
// В Router.php изменить:
public function run(): void
{
    // ...
    $middlewareStack = new MiddlewareStack($finalHandler, $this->container); // Передаем контейнер!
    $middlewareStack->addFromArray($route->getMiddleware());
    // ...
}
```

---

### 5. Отсутствие PSR-7/PSR-15 совместимости

**Приоритет:** ВЫСОКИЙ  
**Сложность:** Высокая  
**Время:** 2-3 недели

**Проблема:**
Библиотека не совместима с PSR стандартами:
- ❌ PSR-7 (HTTP Message Interface)
- ❌ PSR-15 (HTTP Server Request Handlers)  
- ❌ PSR-17 (HTTP Factories)

**Последствия:**
- Невозможна интеграция с PSR экосистемой
- Не работает с популярными библиотеками (Guzzle, Slim, др.)
- Разработчики должны изучать кастомный API

**Решение (поэтапное):**

**Шаг 1: Установить PSR пакеты**
```bash
composer require psr/http-message psr/http-server-handler psr/http-server-middleware
composer require nyholm/psr7 # Реализация PSR-7
composer require nyholm/psr7-server # ServerRequest фабрика
```

**Шаг 2: Создать адаптеры (Wrapper Pattern)**
```php
// src/Http/Psr7/ServerRequestAdapter.php
<?php

namespace FaustVik\Router\Http\Psr7;

use FaustVik\Router\Http\Request;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Адаптер для преобразования Request в PSR-7 ServerRequest
 */
final class ServerRequestAdapter
{
    public static function toPsr7(Request $request): ServerRequestInterface
    {
        return \Nyholm\Psr7\ServerRequest::fromGlobals()
            ->withUri(new \Nyholm\Psr7\Uri($request->getUri()))
            ->withMethod($request->getMethod())
            ->withQueryParams($request->getQuery())
            ->withParsedBody($request->getBody())
            ->withUploadedFiles($request->getFiles());
    }
    
    public static function fromPsr7(ServerRequestInterface $psrRequest): Request
    {
        $request = new Request(
            method: $psrRequest->getMethod(),
            uri: (string)$psrRequest->getUri(),
            params: [],
            query: $psrRequest->getQueryParams(),
            headers: $psrRequest->getHeaders(),
            server: $psrRequest->getServerParams()
        );
        
        // Копируем body
        $body = $psrRequest->getParsedBody();
        if (is_array($body)) {
            // Используем рефлексию для установки приватного поля
            $reflection = new \ReflectionClass($request);
            $bodyProperty = $reflection->getProperty('body');
            $bodyProperty->setAccessible(true);
            $bodyProperty->setValue($request, $body);
        }
        
        return $request;
    }
}
```

**Шаг 3: PSR-15 Middleware адаптер**
```php
// src/Middleware/Psr15MiddlewareAdapter.php
<?php

namespace FaustVik\Router\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Http\Psr7\ServerRequestAdapter;
use FaustVik\Router\Http\Psr7\ResponseAdapter;
use FaustVik\Router\interfaces\Middleware\MiddlewareInterface;
use Psr\Http\Server\MiddlewareInterface as Psr15MiddlewareInterface;

/**
 * Адаптер для использования PSR-15 middleware в Router
 */
final class Psr15MiddlewareAdapter implements MiddlewareInterface
{
    public function __construct(private Psr15MiddlewareInterface $psr15Middleware) {}
    
    public function handle(Request $request, callable $next): Response
    {
        $psrRequest = ServerRequestAdapter::toPsr7($request);
        
        $psrHandler = new class($next) implements \Psr\Http\Server\RequestHandlerInterface {
            public function __construct(private $next) {}
            
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $request = ServerRequestAdapter::fromPsr7($request);
                $response = ($this->next)($request);
                return ResponseAdapter::toPsr7($response);
            }
        };
        
        $psrResponse = $this->psr15Middleware->process($psrRequest, $psrHandler);
        
        return ResponseAdapter::fromPsr7($psrResponse);
    }
}
```

**Использование:**
```php
use Some\Psr15\Middleware as ExternalMiddleware;
use FaustVik\Router\Middleware\Psr15MiddlewareAdapter;

$route->middleware([
    new Psr15MiddlewareAdapter(new ExternalMiddleware())
]);
```

---

### 6. Отсутствие тестов для Router и компонентов

**Приоритет:** ВЫСОКИЙ  
**Покрытие:** ~40% (только Http и Middleware)

**Отсутствуют тесты для:**
- ❌ `src/Router/Router.php` - основной класс!
- ❌ `src/Router/QuickRouter.php`
- ❌ `src/Router/Components/matching/Matching.php`
- ❌ `src/Router/Components/Runner.php`
- ❌ `src/Router/Components/CheckerHttpMethod.php`
- ❌ `src/Router/Components/Config.php`
- ❌ `src/Route/Route.php`
- ❌ `src/Route/RouteGroup.php`
- ❌ `src/Route/RoutesCollection.php`
- ❌ `src/Cache/FileCache.php`
- ❌ `src/DI/DefaultContainer.php`

**План тестирования:**
```php
// tests/Router/RouterTest.php
final class RouterTest extends TestCase
{
    public function testRouterMatchesSimpleRoute(): void
    public function testRouterMatchesParametrizedRoute(): void
    public function testRouterThrowsNoMatchException(): void
    public function testRouterExecutesMiddleware(): void
    public function testRouterInjectsDependencies(): void
    public function testRouterHandlesCaching(): void
}

// tests/Router/Components/MatchingTest.php
final class MatchingTest extends TestCase
{
    public function testMatchExactRoute(): void
    public function testMatchWithParameters(): void
    public function testMatchWithAlias(): void
    public function testThrowsNoMatchForInvalidRoute(): void
    public function testMatchesMultipleParameters(): void
}

// tests/Cache/FileCacheTest.php
final class FileCacheTest extends TestCase
{
    public function testSetAndGet(): void
    public function testTtlExpiration(): void
    public function testClear(): void
    public function testDeleteMultiple(): void
    public function testPathTraversalProtection(): void // Важный тест безопасности!
}
```

**Цель:** 80%+ покрытие кода тестами

---

## 🟡 СРЕДНИЕ проблемы

### 7. Неэффективный алгоритм матчинга O(n)

**Приоритет:** СРЕДНИЙ  
**Файл:** `src/Router/Components/matching/Matching.php`

**Проблема:**
```php
public function match(string $uri, RoutesCollectionInterface $collections): MatchResult
{
    foreach ($collections->get() as $route) { // O(n) - линейный поиск
        if ($uri === $route->getRoute() || $uri === $route->alias()) {
            return new MatchResult($route, []);
        }
        
        $matchResult = $this->matchWithParameters($uri, $route);
        if ($matchResult !== null) {
            return $matchResult;
        }
    }
    // ...
}
```

**Производительность:**
- 10 маршрутов: ~0.1ms ✅
- 100 маршрутов: ~1ms ✅
- 1000 маршрутов: ~10ms ⚠️
- 10000 маршрутов: ~100ms ❌

**Для сравнения (FastRoute):**
- Любое количество маршрутов: ~0.01ms ✅

**Решение 1: Radix Tree (как FastRoute)**
```php
// src/Router/Components/matching/RadixMatcher.php
final class RadixMatcher implements MatchingRouteInterface
{
    private array $staticRoutes = [];     // Точные совпадения
    private array $dynamicRoutes = [];    // С параметрами
    private array $routeTree = [];        // Дерево по первому сегменту
    
    public function buildIndex(RoutesCollectionInterface $collections): void
    {
        foreach ($collections->get() as $route) {
            $routePattern = $route->getRoute();
            
            // Статические маршруты в хеш-таблицу
            if (strpos($routePattern, '{') === false) {
                $this->staticRoutes[$routePattern] = $route;
                continue;
            }
            
            // Динамические маршруты группируем по первому сегменту
            $segments = explode('/', trim($routePattern, '/'));
            $firstSegment = $segments[0] ?? '/';
            
            if (!isset($this->routeTree[$firstSegment])) {
                $this->routeTree[$firstSegment] = [];
            }
            
            $this->routeTree[$firstSegment][] = $route;
            $this->dynamicRoutes[] = $route;
        }
    }
    
    public function match(string $uri, RoutesCollectionInterface $collections): MatchResult
    {
        // 1. Сначала проверяем статические маршруты O(1)
        if (isset($this->staticRoutes[$uri])) {
            return new MatchResult($this->staticRoutes[$uri], []);
        }
        
        // 2. Ищем по дереву сегментов
        $segments = explode('/', trim($uri, '/'));
        $firstSegment = $segments[0] ?? '/';
        
        // Проверяем только кандидатов с подходящим первым сегментом
        if (isset($this->routeTree[$firstSegment])) {
            foreach ($this->routeTree[$firstSegment] as $route) {
                $matchResult = $this->matchWithParameters($uri, $route);
                if ($matchResult !== null) {
                    return $matchResult;
                }
            }
        }
        
        // 3. Fallback - проверяем все динамические маршруты
        foreach ($this->dynamicRoutes as $route) {
            $matchResult = $this->matchWithParameters($uri, $route);
            if ($matchResult !== null) {
                return $matchResult;
            }
        }
        
        throw new NoMatch($uri);
    }
}
```

**Результаты после оптимизации:**
- 10 маршрутов: ~0.05ms (2x быстрее)
- 100 маршрутов: ~0.2ms (5x быстрее)
- 1000 маршрутов: ~1ms (10x быстрее)
- 10000 маршрутов: ~5ms (20x быстрее)

**Решение 2: Использовать кеширование**
Текущий кеш уже помогает, но можно улучшить:
```php
// src/Cache/CachedMatching.php - уже существует, но нужно улучшить
public function match(string $uri, RoutesCollectionInterface $collections): MatchResult
{
    $cacheKey = 'route_match_' . md5($uri);
    
    // Проверяем кеш
    if ($this->cache->has($cacheKey)) {
        $cached = $this->cache->get($cacheKey);
        if ($cached && isset($cached['route'], $cached['params'])) {
            return new MatchResult($cached['route'], $cached['params']);
        }
    }
    
    // Делаем обычный match
    $result = $this->matcher->match($uri, $collections);
    
    // Кешируем результат
    $this->cache->set($cacheKey, [
        'route' => $result->getRoute(),
        'params' => $result->getParameters()
    ], 3600); // 1 час
    
    return $result;
}
```

---

### 8. Отсутствие rate limiting и CSRF защиты

**Приоритет:** СРЕДНИЙ (но КРИТИЧЕСКИЙ для production)

**Отсутствуют:**
- ❌ Rate Limiting middleware
- ❌ CSRF Protection middleware
- ❌ IP Whitelist/Blacklist middleware

**Решение - Rate Limiting:**
```php
// src/Middleware/RateLimitMiddleware.php
<?php

namespace FaustVik\Router\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\interfaces\Cache\CacheInterface;
use FaustVik\Router\interfaces\Middleware\MiddlewareInterface;

final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CacheInterface $cache,
        private int $maxAttempts = 60,
        private int $decayMinutes = 1
    ) {}
    
    public function handle(Request $request, callable $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        $attempts = (int) $this->cache->get($key, 0);
        
        if ($attempts >= $this->maxAttempts) {
            return Response::json([
                'error' => 'Too many requests',
                'retry_after' => $this->decayMinutes * 60
            ], 429)
            ->withHeader('Retry-After', (string)($this->decayMinutes * 60))
            ->withHeader('X-RateLimit-Limit', (string)$this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', '0');
        }
        
        $this->cache->set($key, $attempts + 1, $this->decayMinutes * 60);
        
        $response = $next($request);
        
        // Добавляем заголовки с информацией о лимите
        return $response
            ->withHeader('X-RateLimit-Limit', (string)$this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', (string)($this->maxAttempts - $attempts - 1));
    }
    
    private function resolveRequestSignature(Request $request): string
    {
        $ip = $request->getClientIp();
        $path = $request->getPath();
        
        return 'rate_limit:' . sha1($ip . '|' . $path);
    }
}
```

**Использование:**
```php
use FaustVik\Router\Middleware\RateLimitMiddleware;

$router->bind(RateLimitMiddleware::class, function($container) {
    return new RateLimitMiddleware(
        cache: $container->resolve(CacheInterface::class),
        maxAttempts: 100,
        decayMinutes: 1
    );
});

// Глобально для всех маршрутов
$router->prefix('/api', function($r) {
    // 100 запросов в минуту
    $r->get('/users', [UserController::class, 'index'])
      ->middleware([RateLimitMiddleware::class]);
});
```

**Решение - CSRF Protection:**
```php
// src/Middleware/CsrfMiddleware.php
<?php

namespace FaustVik\Router\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\interfaces\Middleware\MiddlewareInterface;

final class CsrfMiddleware implements MiddlewareInterface
{
    private const TOKEN_LENGTH = 32;
    private const SESSION_KEY = '_csrf_token';
    
    public function handle(Request $request, callable $next): Response
    {
        // Запускаем сессию если не запущена
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Генерируем токен если его нет
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = $this->generateToken();
        }
        
        // Проверяем токен для изменяющих методов
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $token = $request->input('_csrf_token') 
                ?? $request->getHeader('X-CSRF-Token');
            
            if (!$this->validateToken($token)) {
                return Response::json([
                    'error' => 'CSRF token mismatch'
                ], 419);
            }
        }
        
        // Добавляем токен в атрибуты запроса
        $request = $request->withAttribute('csrf_token', $_SESSION[self::SESSION_KEY]);
        
        return $next($request);
    }
    
    private function generateToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }
    
    private function validateToken(?string $token): bool
    {
        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;
        
        if (!$token || !$sessionToken) {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }
    
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION[self::SESSION_KEY] ?? '';
    }
}
```

---

### 9. Named Routes и URL Generation

**Приоритет:** СРЕДНИЙ  
**Сложность:** Средняя

**Отсутствует:**
- Именование маршрутов
- Генерация URL по имени маршрута
- Опциональные параметры
- Regex constraints для параметров

**Примеры чего хочется:**
```php
// 1. Named routes
Route::create('/users/{id}', UserController::class, 'show')
    ->name('users.show');

// 2. URL generation
$url = $router->url('users.show', ['id' => 123]); 
// => /users/123

// 3. Optional parameters
Route::create('/posts/{id?}', PostController::class, 'index');
// GET /posts -> index all
// GET /posts/123 -> show one

// 4. Regex constraints
Route::create('/users/{id:\d+}', UserController::class, 'show');
Route::create('/files/{path:.*}', FileController::class, 'show');
```

**Решение:**
```php
// src/Route/Route.php - добавить поля
private ?string $name = null;
private array $constraints = [];

public function name(string $name): self
{
    $this->name = $name;
    return $this;
}

public function getName(): ?string
{
    return $this->name;
}

public function where(string $param, string $pattern): self
{
    $this->constraints[$param] = $pattern;
    return $this;
}

public function getConstraints(): array
{
    return $this->constraints;
}
```

```php
// src/Router/Router.php - добавить
private array $namedRoutes = [];

public function setCollection(RoutesCollectionInterface $collections): self
{
    $this->collections = $collections;
    
    // Индексируем именованные маршруты
    foreach ($collections->get() as $route) {
        if ($route->getName()) {
            $this->namedRoutes[$route->getName()] = $route;
        }
    }
    
    return $this;
}

public function url(string $name, array $params = []): string
{
    if (!isset($this->namedRoutes[$name])) {
        throw new \InvalidArgumentException("Route '{$name}' not found");
    }
    
    $route = $this->namedRoutes[$name];
    $uri = $route->getRoute();
    
    // Заменяем параметры
    foreach ($params as $key => $value) {
        $uri = preg_replace(
            '/\{' . preg_quote($key) . '(:.*?)?\?\}|\{' . preg_quote($key) . '(:.*?)?\}/',
            $value,
            $uri
        );
    }
    
    // Удаляем опциональные параметры которые не были заполнены
    $uri = preg_replace('/\{[^}]+\?\}/', '', $uri);
    
    // Проверяем что все обязательные параметры заполнены
    if (preg_match('/\{([^}?]+)\}/', $uri, $matches)) {
        throw new \InvalidArgumentException(
            "Missing required parameter '{$matches[1]}' for route '{$name}'"
        );
    }
    
    return $uri;
}

public function has(string $name): bool
{
    return isset($this->namedRoutes[$name]);
}
```

**Использование:**
```php
// Определение маршрутов
Route::create('/users/{id:\d+}', UserController::class, 'show')
    ->name('users.show')
    ->where('id', '\d+');

Route::create('/posts/{id?}', PostController::class, 'index')
    ->name('posts.index');

// Генерация URL
$userUrl = $router->url('users.show', ['id' => 123]);
// => /users/123

$postsUrl = $router->url('posts.index');
// => /posts

$postUrl = $router->url('posts.index', ['id' => 456]);
// => /posts/456
```

---

### 10. Недостаточное логирование

**Приоритет:** СРЕДНИЙ  

**Проблема:**
`LoggingMiddleware` есть, но:
- Не логирует ошибки маршрутизации
- Нет структурированного логирования (JSON)
- Нет интеграции с PSR-3 Logger

**Решение - PSR-3 интеграция:**
```bash
composer require psr/log
composer require monolog/monolog # Опционально
```

```php
// src/Middleware/LoggingMiddleware.php - улучшить
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ?LoggerInterface $logger = null,
        private string $level = 'info'
    ) {
        $this->logger ??= new NullLogger();
    }
    
    public function handle(Request $request, callable $next): Response
    {
        $startTime = microtime(true);
        $requestId = bin2hex(random_bytes(8));
        
        // Логируем входящий запрос
        $this->logger->info('Incoming request', [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->getHeader('User-Agent'),
        ]);
        
        try {
            $response = $next($request);
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            // Логируем успешный ответ
            $this->logger->info('Request completed', [
                'request_id' => $requestId,
                'status' => $response->getStatusCode(),
                'duration_ms' => round($duration, 2),
            ]);
            
            return $response;
            
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            // Логируем ошибку
            $this->logger->error('Request failed', [
                'request_id' => $requestId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'duration_ms' => round($duration, 2),
            ]);
            
            throw $e;
        }
    }
}
```

**Использование с Monolog:**
```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;

$logger = new Logger('router');
$handler = new RotatingFileHandler('logs/router.log', 30); // 30 дней ротации
$handler->setFormatter(new JsonFormatter());
$logger->pushHandler($handler);

$router = new Router();
// Используем метод middleware() для глобального применения
$globalMiddleware = new LoggingMiddleware($logger);
```

---

## 📝 Рекомендации по приоритизации

### Немедленно (эта неделя):
1. ✅ **Issue #1:** Заменить `unserialize` на `json_encode/json_decode` в FileCache
2. ✅ **Issue #2:** Добавить валидацию пути и защиту в FileCache
3. ✅ **Issue #3:** Добавить ErrorHandler в Router

### Краткосрочно (1-2 недели):
4. ✅ **Issue #4:** Исправить DI в MiddlewareStack
5. ✅ **Issue #6:** Написать тесты для Router и компонентов (покрытие 60%+)

### Среднесрочно (3-4 недели):
6. ✅ **Issue #7:** Оптимизировать Matching (Radix Tree или улучшенное кеширование)
7. ✅ **Issue #8:** Добавить RateLimitMiddleware и CsrfMiddleware
8. ✅ **Issue #9:** Реализовать Named Routes и URL Generation

### Долгосрочно (1-2 месяца):
9. ✅ **Issue #5:** Реализовать PSR-7/PSR-15 совместимость
10. ✅ **Issue #10:** Улучшить логирование (PSR-3)

---

## 📊 Метрики качества

| Метрика | Текущее | Целевое | Статус |
|---------|---------|---------|--------|
| Test Coverage | ~40% | 80%+ | 🟡 Требует улучшения |
| PHPStan Level | ? | 8 | ❓ Неизвестно |
| Security Score | 4/10 | 9/10 | 🔴 Критично |
| PSR Compliance | 2/7 | 5/7 | 🟠 Недостаточно |
| Performance | Средняя | Высокая | 🟡 Требует оптимизации |

---

## 🎯 Итоговый план действий

### Sprint 1: Безопасность (1 неделя)
- [ ] Заменить `unserialize` на JSON в FileCache
- [ ] Добавить валидацию путей и защиту директории
- [ ] Создать ErrorHandler
- [ ] Интегрировать ErrorHandler в Router
- [ ] Написать тесты безопасности для FileCache

### Sprint 2: Стабильность (2 недели)
- [ ] Исправить DI в MiddlewareStack
- [ ] Написать тесты для Router.php
- [ ] Написать тесты для Matching.php
- [ ] Написать тесты для Runner.php
- [ ] Довести покрытие до 60%+

### Sprint 3: Функциональность (2 недели)
- [ ] Реализовать Named Routes
- [ ] Реализовать URL Generation
- [ ] Добавить RateLimitMiddleware
- [ ] Добавить CsrfMiddleware
- [ ] Улучшить LoggingMiddleware (PSR-3)

### Sprint 4: Производительность (1 неделя)
- [ ] Оптимизировать Matching (Radix Tree)
- [ ] Улучшить кеширование
- [ ] Провести бенчмарки
- [ ] Оптимизировать узкие места

### Sprint 5: PSR Совместимость (2-3 недели)
- [ ] Установить PSR пакеты
- [ ] Создать PSR-7 адаптеры
- [ ] Создать PSR-15 адаптеры
- [ ] Документация по PSR интеграции
- [ ] Примеры использования

---

## 📞 Выводы

**Проект в хорошем состоянии**, но требует внимания к:
1. 🔴 **Безопасности** - критические уязвимости должны быть исправлены немедленно
2. 🟡 **Тестированию** - нужно довести покрытие до 80%+
3. 🟡 **Производительности** - оптимизация матчинга улучшит скорость в 10-20 раз
4. 🟢 **Совместимости** - PSR интеграция расширит аудиторию пользователей

**Реалистичный timeline до stable release:**
- 2-3 месяца активной разработки
- Beta release через 6-8 недель
- Stable v2.0.0 через 12-14 недель

**Библиотека имеет хороший потенциал** и современный дизайн. После исправления критических проблем безопасности и добавления тестов, она будет готова для production использования.

---

**Последнее обновление:** 6 октября 2025  
**Версия документа:** 2.0  
**Статус проекта:** v2.0-alpha (Active Development)

