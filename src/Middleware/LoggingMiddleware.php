<?php

declare(strict_types=1);

namespace FaustVik\Router\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\interfaces\Middleware\MiddlewareInterface;

/**
 * Middleware for HTTP request logging
 *
 * Logs information about each request: method, URI, IP, User-Agent,
 * response code and execution time. Supports custom loggers.
 *
 * Usage example:
 * ```php
 * // Log to file
 * $logger = new LoggingMiddleware('logs/app.log');
 *
 * // With custom logger (PSR-3)
 * $logger = new LoggingMiddleware(null, function($message, $context) {
 *     $psrLogger->info($message, $context);
 * });
 * ```
 *
 * @package FaustVik\Router\Middleware
 */
final class LoggingMiddleware implements MiddlewareInterface
{
    private ?string $logFile;

    /**
     * @var callable|null Кастомная функция логирования
     */
    private $customLogger;

    private bool $includeUserAgent;
    private bool $includeIp;

    /**
     * HTTP статус коды и их текстовые описания
     *
     * @var array<int, string>
     */
    private const STATUS_TEXTS = [
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    /**
     * @param string|null $logFile Путь к файлу логов (null если используется кастомный логгер)
     * @param callable|null $customLogger Кастомный логгер: fn(string $message, array $context): void
     * @param bool $includeUserAgent Включать User-Agent в лог
     * @param bool $includeIp Включать IP адрес в лог
     */
    public function __construct(
        ?string $logFile = 'router.log',
        ?callable $customLogger = null,
        bool $includeUserAgent = true,
        bool $includeIp = true
    ) {
        $this->logFile = $logFile;
        $this->customLogger = $customLogger;
        $this->includeUserAgent = $includeUserAgent;
        $this->includeIp = $includeIp;
    }

    /**
     * Обрабатывает запрос и логирует его
     *
     * @param Request $request HTTP запрос
     * @param callable $next Следующий middleware в цепочке
     * @return Response HTTP ответ
     */
    public function handle(Request $request, callable $next): Response
    {
        $startTime = microtime(true);

        // Собираем данные запроса
        $method = $request->getMethod();
        $uri = $request->getUri();
        $userAgentRaw = $this->includeUserAgent ? $request->getHeader('User-Agent', 'Unknown') : null;
        $userAgent = $userAgentRaw !== null && is_string($userAgentRaw) ? $userAgentRaw : ($userAgentRaw !== null ? 'Unknown' : null);
        $ip = $this->includeIp ? $request->getClientIp() : null;

        // Выполняем следующий middleware
        $response = $next($request);

        // Вычисляем время выполнения в миллисекундах
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $statusCode = $response->getStatusCode();

        // Логируем результат
        $this->log($method, $uri, $statusCode, $duration, $ip, $userAgent);

        return $response;
    }

    /**
     * Записывает лог сообщение
     *
     * @param string $method HTTP метод
     * @param string $uri URI запроса
     * @param int $statusCode Код ответа
     * @param float $duration Время выполнения в мс
     * @param string|null $ip IP адрес клиента
     * @param string|null $userAgent User-Agent клиента
     */
    private function log(
        string $method,
        string $uri,
        int $statusCode,
        float $duration,
        ?string $ip,
        ?string $userAgent
    ): void {
        $context = [
            'method' => $method,
            'uri' => $uri,
            'status_code' => $statusCode,
            'duration_ms' => $duration,
            'status_text' => $this->getStatusText($statusCode),
        ];

        if ($ip !== null) {
            $context['ip'] = $ip;
        }

        if ($userAgent !== null) {
            $context['user_agent'] = $userAgent;
        }

        // Используем кастомный логгер если есть
        if ($this->customLogger !== null) {
            $message = $this->formatLogMessage($context);
            ($this->customLogger)($message, $context);
            return;
        }

        // Иначе пишем в файл
        if ($this->logFile !== null) {
            $message = $this->formatLogMessage($context);
            $this->writeToFile($message);
        }
    }

    /**
     * Форматирует сообщение для лога
     *
     * @param array<string, mixed> $context Контекст запроса
     * @return string Отформатированное сообщение
     */
    private function formatLogMessage(array $context): string
    {
        $parts = [
            '[' . date('Y-m-d H:i:s') . ']',
        ];

        if (isset($context['ip'])) {
            $parts[] = $context['ip'];
        }

        $parts[] = $context['method'];
        $parts[] = $context['uri'];

        if (isset($context['user_agent'])) {
            $parts[] = '"' . $context['user_agent'] . '"';
        }

        $parts[] = $context['status_code'];
        $parts[] = $context['duration_ms'] . 'ms';
        $parts[] = $context['status_text'];

        return implode(' ', $parts) . "\n";
    }

    /**
     * Записывает сообщение в файл логов
     *
     * @param string $message Сообщение для записи
     */
    private function writeToFile(string $message): void
    {
        if ($this->logFile === null) {
            return;
        }

        // Создаем директорию если не существует
        $dir = dirname($this->logFile);
        if ($dir !== '.' && !is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        // Записываем с блокировкой файла
        @file_put_contents($this->logFile, $message, FILE_APPEND | LOCK_EX);
    }

    /**
     * Возвращает текстовое описание HTTP статус кода
     *
     * @param int $statusCode HTTP статус код
     * @return string Текстовое описание
     */
    private function getStatusText(int $statusCode): string
    {
        return self::STATUS_TEXTS[$statusCode] ?? 'Unknown';
    }
}
