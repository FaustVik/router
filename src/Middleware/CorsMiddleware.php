<?php

declare(strict_types=1);

namespace FaustVik\Router\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\interfaces\Middleware\MiddlewareInterface;

final class CorsMiddleware implements MiddlewareInterface
{
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;
    private bool $allowCredentials;
    private int $maxAge;

    public function __construct(
        array $allowedOrigins = ['*'],
        array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        array $allowedHeaders = ['Content-Type', 'Authorization'],
        bool $allowCredentials = false,
        int $maxAge = 3600
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
        $this->allowCredentials = $allowCredentials;
        $this->maxAge = $maxAge;
    }

    public function handle(Request $request, callable $next): Response
    {
        $origin = $request->getHeader('Origin');

        // Для OPTIONS запросов возвращаем preflight response
        if ($request->getMethod() === 'OPTIONS') {
            return $this->createPreflightResponse($origin);
        }

        // Выполняем следующий middleware
        $response = $next($request);

        // Добавляем CORS заголовки к ответу
        return $this->addCorsHeaders($response, $origin);
    }

    private function createPreflightResponse(?string $origin): Response
    {
        $headers = [
            'Access-Control-Allow-Methods' => implode(', ', $this->allowedMethods),
            'Access-Control-Allow-Headers' => implode(', ', $this->allowedHeaders),
            'Access-Control-Max-Age' => (string) $this->maxAge,
        ];

        if ($this->isOriginAllowed($origin)) {
            $headers['Access-Control-Allow-Origin'] = $origin ?: '*';
        }

        if ($this->allowCredentials) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        return new Response('', 200, $headers);
    }

    private function addCorsHeaders(Response $response, ?string $origin): Response
    {
        $headers = [];

        if ($this->isOriginAllowed($origin)) {
            $headers['Access-Control-Allow-Origin'] = $origin ?: '*';
        }

        if ($this->allowCredentials) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        return $response->withHeaders($headers);
    }

    private function isOriginAllowed(?string $origin): bool
    {
        if (in_array('*', $this->allowedOrigins, true)) {
            return true;
        }

        return $origin && in_array($origin, $this->allowedOrigins, true);
    }
}
