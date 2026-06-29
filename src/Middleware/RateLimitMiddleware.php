<?php

declare(strict_types=1);

namespace FaustVik\Router\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Interfaces\Cache\CacheInterface;
use FaustVik\Router\Interfaces\Middleware\MiddlewareInterface;

/**
 * Middleware for request rate limiting
 *
 * Protects application from DDoS attacks and excessive API usage by
 * limiting number of requests from single IP address within time period.
 *
 * Adds standard X-RateLimit-* headers to responses:
 * - X-RateLimit-Limit: maximum number of requests
 * - X-RateLimit-Remaining: remaining number of requests
 * - X-RateLimit-Reset: Unix timestamp when limit resets
 * - Retry-After: seconds until next attempt (only on 429)
 *
 * Usage example:
 * ```php
 * $cache = new FileCache();
 * $rateLimit = new RateLimitMiddleware(
 *     cache: $cache,
 *     maxAttempts: 60,        // 60 requests
 *     decaySeconds: 60,       // per minute
 *     includePathInKey: true  // separate limit for each endpoint
 * );
 * ```
 *
 * @package FaustVik\Router\Middleware
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    private const CACHE_PREFIX = 'rate_limit:';
    private const HEADER_LIMIT = 'X-RateLimit-Limit';
    private const HEADER_REMAINING = 'X-RateLimit-Remaining';
    private const HEADER_RESET = 'X-RateLimit-Reset';
    private const HEADER_RETRY_AFTER = 'Retry-After';

    /**
     * @param CacheInterface $cache Кеш для хранения счетчиков запросов
     * @param int $maxAttempts Максимальное количество запросов
     * @param int $decaySeconds Время сброса лимита в секундах
     * @param bool $includePathInKey Включать путь в ключ (для раздельных лимитов по endpoint)
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $maxAttempts = 60,
        private readonly int $decaySeconds = 60,
        private readonly bool $includePathInKey = false
    ) {
    }

    /**
     * Обрабатывает входящий запрос с проверкой лимита
     *
     * Проверяет количество запросов с данного IP и возвращает 429 Too Many Requests
     * если лимит превышен. Добавляет информационные заголовки о лимитах.
     *
     * @param Request $request HTTP запрос
     * @param callable $next Следующий middleware в цепочке
     * @return Response HTTP ответ с заголовками rate limiting
     */
    public function handle(Request $request, callable $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        $attempts = $this->getAttempts($key);
        $resetTime = $this->getResetTime($key);

        // Проверяем превышение лимита
        if ($attempts >= $this->maxAttempts) {
            return $this->buildLimitExceededResponse($resetTime);
        }

        // Увеличиваем счетчик
        $this->incrementAttempts($key, $resetTime);

        // Обрабатываем запрос
        $response = $next($request);

        // Добавляем заголовки rate limiting
        return $this->addRateLimitHeaders(
            response: $response,
            attempts: $attempts + 1,
            resetTime: $resetTime
        );
    }

    /**
     * Генерирует уникальный ключ для идентификации источника запроса
     *
     * Использует IP адрес клиента, опционально добавляет путь запроса
     * для раздельных лимитов на разные endpoint'ы.
     *
     * @param Request $request HTTP запрос
     * @return string Уникальный ключ для кеша
     */
    private function resolveRequestSignature(Request $request): string
    {
        $ip = $request->getClientIp();
        $path = $this->includePathInKey ? $request->getPath() : '';

        return self::CACHE_PREFIX . sha1($ip . '|' . $path);
    }

    /**
     * Получает текущее количество попыток из кеша
     *
     * @param string $key Ключ кеша
     * @return int Количество попыток
     */
    private function getAttempts(string $key): int
    {
        $data = $this->cache->get($key);

        if (!is_array($data) || !isset($data['attempts'])) {
            return 0;
        }

        return is_numeric($data['attempts']) ? (int) $data['attempts'] : 0;
    }

    /**
     * Получает время сброса лимита из кеша или вычисляет новое
     *
     * @param string $key Ключ кеша
     * @return int Unix timestamp времени сброса
     */
    private function getResetTime(string $key): int
    {
        $data = $this->cache->get($key);

        if (is_array($data) && isset($data['reset_time'])) {
            return is_numeric($data['reset_time']) ? (int) $data['reset_time'] : time() + $this->decaySeconds;
        }

        return time() + $this->decaySeconds;
    }

    /**
     * Увеличивает счетчик попыток в кеше
     *
     * @param string $key Ключ кеша
     * @param int $resetTime Время сброса лимита
     */
    private function incrementAttempts(string $key, int $resetTime): void
    {
        $attempts = $this->getAttempts($key) + 1;

        $this->cache->set(
            key: $key,
            value: [
                'attempts' => $attempts,
                'reset_time' => $resetTime,
            ],
            ttl: $this->decaySeconds
        );
    }

    /**
     * Создает ответ 429 Too Many Requests при превышении лимита
     *
     * @param int $resetTime Unix timestamp времени сброса лимита
     * @return Response
     */
    private function buildLimitExceededResponse(int $resetTime): Response
    {
        $retryAfter = max(0, $resetTime - time());

        $response = Response::json(
            data: [
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $retryAfter,
            ],
            statusCode: 429
        );

        return $this->addRateLimitHeaders(
            response: $response,
            attempts: $this->maxAttempts,
            resetTime: $resetTime,
            isExceeded: true
        );
    }

    /**
     * Добавляет заголовки rate limiting в ответ
     *
     * @param Response $response HTTP ответ
     * @param int $attempts Текущее количество попыток
     * @param int $resetTime Unix timestamp времени сброса
     * @param bool $isExceeded Превышен ли лимит
     * @return Response Ответ с добавленными заголовками
     */
    private function addRateLimitHeaders(
        Response $response,
        int $attempts,
        int $resetTime,
        bool $isExceeded = false
    ): Response {
        $remaining = max(0, $this->maxAttempts - $attempts);

        $response = $response
            ->withHeader(self::HEADER_LIMIT, (string) $this->maxAttempts)
            ->withHeader(self::HEADER_REMAINING, (string) $remaining)
            ->withHeader(self::HEADER_RESET, (string) $resetTime);

        if ($isExceeded) {
            $retryAfter = max(0, $resetTime - time());
            $response = $response->withHeader(self::HEADER_RETRY_AFTER, (string) $retryAfter);
        }

        return $response;
    }

    /**
     * Очищает все данные rate limiting из кеша
     *
     * Полезно для тестирования или сброса лимитов
     *
     * @return bool Успешность операции
     */
    public function clearLimits(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Получает текущий статус лимита для конкретного запроса
     *
     * Полезно для отладки и мониторинга
     *
     * @param Request $request HTTP запрос
     * @return array<string, int> ['attempts' => int, 'remaining' => int, 'reset_time' => int]
     */
    public function getLimitStatus(Request $request): array
    {
        $key = $this->resolveRequestSignature($request);
        $attempts = $this->getAttempts($key);
        $resetTime = $this->getResetTime($key);

        return [
            'attempts' => $attempts,
            'remaining' => max(0, $this->maxAttempts - $attempts),
            'reset_time' => $resetTime,
        ];
    }
}
