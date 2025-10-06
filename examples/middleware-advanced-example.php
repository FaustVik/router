<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Middleware\CorsMiddleware;
use FaustVik\Router\Middleware\LoggingMiddleware;
use FaustVik\Router\Middleware\MiddlewareStack;
use FaustVik\Router\Router\QuickRouter;

/**
 * Продвинутый пример использования Middleware
 *
 * Демонстрирует:
 * - Кастомный логгер совместимый с PSR-3
 * - Динамическая настройка CORS middleware
 * - MiddlewareStack для групповой обработки
 * - Создание собственного middleware
 */

// 1. Создаем простой PSR-3 совместимый логгер
class SimpleLogger
{
    private string $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    private function log(string $level, string $message, array $context): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logLine = "[$timestamp] $level: $message $contextStr\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}

// 2. Создаем кастомный middleware для проверки API ключа
class ApiKeyMiddleware implements \FaustVik\Router\interfaces\Middleware\MiddlewareInterface
{
    private array $validApiKeys;

    public function __construct(array $validApiKeys)
    {
        $this->validApiKeys = $validApiKeys;
    }

    public function handle(Request $request, callable $next): Response
    {
        $apiKey = $request->getHeader('X-Api-Key');

        if (!$apiKey) {
            return Response::json([
                'error' => 'Missing API Key',
                'message' => 'X-Api-Key header is required'
            ], 401);
        }

        if (!in_array($apiKey, $this->validApiKeys, true)) {
            return Response::json([
                'error' => 'Invalid API Key',
                'message' => 'The provided API key is not valid'
            ], 403);
        }

        // Добавляем метку успешной проверки API ключа
        $request = $request->withAttribute('api_key_validated', true);

        return $next($request);
    }
}

// 3. Создаем middleware для rate limiting (упрощенный)
class RateLimitMiddleware implements \FaustVik\Router\interfaces\Middleware\MiddlewareInterface
{
    private int $maxRequests;
    private int $timeWindow;
    private array $requests = [];

    public function __construct(int $maxRequests = 100, int $timeWindow = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }

    public function handle(Request $request, callable $next): Response
    {
        $ip = $request->getClientIp();
        $currentTime = time();

        // Очищаем старые записи
        if (isset($this->requests[$ip])) {
            $this->requests[$ip] = array_filter(
                $this->requests[$ip],
                fn($timestamp) => $currentTime - $timestamp < $this->timeWindow
            );
        }

        // Проверяем лимит
        $requestCount = isset($this->requests[$ip]) ? count($this->requests[$ip]) : 0;

        if ($requestCount >= $this->maxRequests) {
            return Response::json([
                'error' => 'Too Many Requests',
                'message' => "Rate limit exceeded. Max $this->maxRequests requests per {$this->timeWindow}s",
                'retry_after' => $this->timeWindow
            ], 429);
        }

        // Добавляем текущий запрос
        $this->requests[$ip][] = $currentTime;

        // Добавляем заголовки rate limit
        $response = $next($request);
        $remaining = $this->maxRequests - $requestCount - 1;

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $remaining))
            ->withHeader('X-RateLimit-Reset', (string) ($currentTime + $this->timeWindow));
    }
}

// 4. Настраиваем компоненты
$logger = new SimpleLogger(__DIR__ . '/logs/app.log');

$loggingMiddleware = new LoggingMiddleware(
    logFile: null, // Не используем файл напрямую
    customLogger: function (string $message, array $context) use ($logger) {
        $logger->info($message, $context);
    },
    includeUserAgent: true,
    includeIp: true
);

// 5. CORS с динамической настройкой
$corsMiddleware = new CorsMiddleware();
$corsMiddleware
    ->setAllowedOrigins(['https://example.com', 'https://*.example.com'])
    ->setAllowedMethods(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'])
    ->setAllowedHeaders(['Content-Type', 'Authorization', 'X-Api-Key'])
    ->setExposedHeaders(['X-Total-Count', 'X-Page-Number']);

// 6. Создаем API key middleware
$apiKeyMiddleware = new ApiKeyMiddleware([
    'dev-key-12345',
    'prod-key-67890',
    'test-key-abcde'
]);

// 7. Rate limiting
$rateLimitMiddleware = new RateLimitMiddleware(
    maxRequests: 10,
    timeWindow: 60 // 10 запросов в минуту
);

// 8. Создаем роутер
$router = new QuickRouter();

// 9. Эндпоинт с полным стеком middleware
$router->get('/api/data', function (Request $request): Response {
    return Response::json([
        'data' => [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
            ['id' => 3, 'name' => 'Item 3'],
        ],
        'meta' => [
            'total' => 3,
            'api_key_validated' => $request->getAttribute('api_key_validated', false)
        ]
    ])->withHeader('X-Total-Count', '3');
})->setMiddleware([
    $corsMiddleware,
    $loggingMiddleware,
    $apiKeyMiddleware,
    $rateLimitMiddleware
]);

// 10. Эндпоинт для проверки rate limit
$router->get('/api/test-rate-limit', function (Request $request): Response {
    return Response::json([
        'message' => 'Request successful',
        'timestamp' => time()
    ]);
})->setMiddleware([$rateLimitMiddleware]);

// 11. Использование MiddlewareStack напрямую
$router->post('/api/complex', function (Request $request): Response {
    return Response::json([
        'message' => 'Complex operation completed',
        'data' => $request->getBody()
    ]);
});

// Создаем обработчик с middleware stack
$request = Request::createFromGlobals();

// Создаем стек для обработки сложных операций
$stack = new MiddlewareStack(function (Request $req) use ($router) {
    return $router->dispatch($req);
});

// Добавляем глобальные middleware
$stack
    ->add($corsMiddleware)
    ->add($loggingMiddleware);

try {
    $response = $stack->execute($request);
    $response->send();
} catch (\Exception $e) {
    $logger->error('Request processing error: ' . $e->getMessage(), [
        'exception' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);

    Response::json([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage()
    ], 500)->send();
}

/*
 * Примеры запросов для тестирования:
 *
 * 1. Запрос с API ключом:
 *    curl -H "X-Api-Key: dev-key-12345" \
 *         http://localhost:8000/api/data
 *
 * 2. Запрос без API ключа (будет 401):
 *    curl http://localhost:8000/api/data
 *
 * 3. Запрос с неверным API ключом (будет 403):
 *    curl -H "X-Api-Key: wrong-key" \
 *         http://localhost:8000/api/data
 *
 * 4. Тест rate limiting (выполнить 11+ раз быстро):
 *    for i in {1..15}; do
 *        curl http://localhost:8000/api/test-rate-limit
 *        echo ""
 *    done
 *
 * 5. CORS preflight:
 *    curl -X OPTIONS \
 *         -H "Origin: https://example.com" \
 *         -H "Access-Control-Request-Method: POST" \
 *         http://localhost:8000/api/data
 */

