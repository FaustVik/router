<?php

declare(strict_types=1);

namespace FaustVik\Router\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Interfaces\Middleware\MiddlewareInterface;

/**
 * Middleware for Cross-Origin Resource Sharing (CORS) handling
 *
 * Automatically handles CORS headers and preflight OPTIONS requests.
 * Supports configuration of allowed origins, methods, headers and credentials.
 *
 * Usage example:
 * ```php
 * // Allow all origins
 * $cors = new CorsMiddleware();
 *
 * // Configure specific origins
 * $cors = new CorsMiddleware(
 *     allowedOrigins: ['https://example.com', 'https://app.example.com'],
 *     allowedMethods: ['GET', 'POST', 'PUT', 'DELETE'],
 *     allowedHeaders: ['Content-Type', 'Authorization', 'X-Api-Key'],
 *     allowCredentials: true,
 *     maxAge: 86400
 * );
 * ```
 *
 * @package FaustVik\Router\Middleware
 */
final class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @var array<string> Разрешенные origins (домены)
     */
    private array $allowedOrigins;

    /**
     * @var array<string> Разрешенные HTTP методы
     */
    private array $allowedMethods;

    /**
     * @var array<string> Разрешенные HTTP заголовки
     */
    private array $allowedHeaders;

    /**
     * @var array<string> Заголовки, которые можно читать из JavaScript
     */
    private array $exposedHeaders;

    /**
     * @var bool Разрешить отправку cookies и авторизационных данных
     */
    private bool $allowCredentials;

    /**
     * @var int Время кэширования preflight запроса (в секундах)
     */
    private int $maxAge;

    /**
     * @param array<string> $allowedOrigins Разрешенные origins ('*' для всех)
     * @param array<string> $allowedMethods Разрешенные HTTP методы
     * @param array<string> $allowedHeaders Разрешенные заголовки запроса
     * @param array<string> $exposedHeaders Заголовки доступные для чтения
     * @param bool $allowCredentials Разрешить credentials (cookies, авторизацию)
     * @param int $maxAge Время кэширования preflight в секундах
     */
    public function __construct(
        array $allowedOrigins = ['*'],
        array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With'],
        array $exposedHeaders = [],
        bool $allowCredentials = false,
        int $maxAge = 3600
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
        $this->exposedHeaders = $exposedHeaders;
        $this->allowCredentials = $allowCredentials;
        $this->maxAge = $maxAge;
    }

    /**
     * Обрабатывает запрос и добавляет CORS заголовки
     *
     * @param Request $request HTTP запрос
     * @param callable $next Следующий middleware в цепочке
     * @return Response HTTP ответ с CORS заголовками
     */
    public function handle(Request $request, callable $next): Response
    {
        $originRaw = $request->getHeader('Origin');
        $origin = is_string($originRaw) ? $originRaw : null;

        // Для OPTIONS запросов (preflight) возвращаем специальный ответ
        if ($request->getMethod() === 'OPTIONS') {
            return $this->createPreflightResponse($origin);
        }

        // Выполняем следующий middleware
        $response = $next($request);

        // Добавляем CORS заголовки к обычному ответу
        return $this->addCorsHeaders($response, $origin);
    }

    /**
     * Создает ответ на preflight запрос (OPTIONS)
     *
     * Preflight запрос - это предварительный запрос, который браузер отправляет
     * перед "сложным" CORS запросом для проверки разрешений.
     *
     * @param string|null $origin Origin из запроса
     * @return Response Preflight ответ с кодом 204 No Content
     */
    private function createPreflightResponse(?string $origin): Response
    {
        $headers = [
            'Access-Control-Allow-Methods' => implode(', ', $this->allowedMethods),
            'Access-Control-Allow-Headers' => implode(', ', $this->allowedHeaders),
            'Access-Control-Max-Age' => (string) $this->maxAge,
        ];

        // Добавляем origin если разрешен
        if ($this->isOriginAllowed($origin)) {
            $headers['Access-Control-Allow-Origin'] = $this->getAllowedOriginValue($origin);
        }

        // Credentials требуют конкретный origin (не *)
        if ($this->allowCredentials && $origin !== null) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        // Exposed headers для preflight
        if (!empty($this->exposedHeaders)) {
            $headers['Access-Control-Expose-Headers'] = implode(', ', $this->exposedHeaders);
        }

        // 204 No Content - стандартный код для успешного preflight
        return new Response('', 204, $headers);
    }

    /**
     * Добавляет CORS заголовки к обычному ответу
     *
     * @param Response $response Исходный ответ
     * @param string|null $origin Origin из запроса
     * @return Response Ответ с добавленными CORS заголовками
     */
    private function addCorsHeaders(Response $response, ?string $origin): Response
    {
        $headers = [];

        // Добавляем origin если разрешен
        if ($this->isOriginAllowed($origin)) {
            $headers['Access-Control-Allow-Origin'] = $this->getAllowedOriginValue($origin);
        }

        // Credentials требуют конкретный origin (не *)
        if ($this->allowCredentials && $origin !== null) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        // Exposed headers позволяют JavaScript читать указанные заголовки
        if (!empty($this->exposedHeaders)) {
            $headers['Access-Control-Expose-Headers'] = implode(', ', $this->exposedHeaders);
        }

        return $response->withHeaders($headers);
    }

    /**
     * Проверяет, разрешен ли указанный origin
     *
     * @param string|null $origin Origin для проверки
     * @return bool true если origin разрешен
     */
    private function isOriginAllowed(?string $origin): bool
    {
        // Если разрешены все origins
        if (in_array('*', $this->allowedOrigins, true)) {
            return true;
        }

        // Проверяем конкретный origin
        if ($origin !== null && in_array($origin, $this->allowedOrigins, true)) {
            return true;
        }

        // Проверяем паттерны с wildcard (например, *.example.com)
        if ($origin !== null) {
            foreach ($this->allowedOrigins as $allowedOrigin) {
                if ($this->matchOriginPattern($origin, $allowedOrigin)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Проверяет соответствие origin паттерну с wildcard
     *
     * @param string $origin Origin для проверки
     * @param string $pattern Паттерн (может содержать *)
     * @return bool true если соответствует
     */
    private function matchOriginPattern(string $origin, string $pattern): bool
    {
        // Если нет wildcard, используем точное сравнение
        if (!str_contains($pattern, '*')) {
            return $origin === $pattern;
        }

        // Преобразуем wildcard паттерн в regex
        // Сначала экранируем специальные символы regex
        $regex = preg_quote($pattern, '/');
        // Заменяем экранированную звездочку на wildcard regex
        $regex = str_replace('\*', '.*', $regex);
        return preg_match('/^' . $regex . '$/', $origin) === 1;
    }

    /**
     * Возвращает значение для заголовка Access-Control-Allow-Origin
     *
     * @param string|null $origin Origin из запроса
     * @return string Значение для заголовка
     */
    private function getAllowedOriginValue(?string $origin): string
    {
        // Если разрешены все origins и credentials не используются, возвращаем *
        if (in_array('*', $this->allowedOrigins, true) && !$this->allowCredentials) {
            return '*';
        }

        // Иначе возвращаем конкретный origin
        return $origin ?? '*';
    }

    /**
     * Устанавливает разрешенные origins
     *
     * @param array<string> $origins Массив origins или ['*'] для всех
     * @return self
     */
    public function setAllowedOrigins(array $origins): self
    {
        $this->allowedOrigins = $origins;
        return $this;
    }

    /**
     * Устанавливает разрешенные HTTP методы
     *
     * @param array<string> $methods Массив HTTP методов
     * @return self
     */
    public function setAllowedMethods(array $methods): self
    {
        $this->allowedMethods = $methods;
        return $this;
    }

    /**
     * Устанавливает разрешенные заголовки
     *
     * @param array<string> $headers Массив заголовков
     * @return self
     */
    public function setAllowedHeaders(array $headers): self
    {
        $this->allowedHeaders = $headers;
        return $this;
    }

    /**
     * Устанавливает exposed заголовки
     *
     * @param array<string> $headers Массив заголовков
     * @return self
     */
    public function setExposedHeaders(array $headers): self
    {
        $this->exposedHeaders = $headers;
        return $this;
    }
}
