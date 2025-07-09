<?php

declare(strict_types=1);

namespace FaustVik\Router\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\interfaces\Middleware\MiddlewareInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Проверяем наличие токена авторизации
        $authHeader = $request->getHeader('Authorization');

        if (!$authHeader) {
            return Response::json(
                ['error' => 'Authorization header is required'],
                401
            );
        }

        // Простая проверка токена (в реальном приложении должна быть более сложная логика)
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return Response::json(
                ['error' => 'Invalid authorization format'],
                401
            );
        }

        $token = substr($authHeader, 7); // Убираем "Bearer "

        // Простая проверка токена
        if (empty($token) || $token === 'invalid') {
            return Response::json(
                ['error' => 'Invalid or expired token'],
                401
            );
        }

        // Добавляем информацию о пользователе в запрос
        $request = $request->withAttribute('user_id', $this->getUserIdFromToken($token));
        $request = $request->withAttribute('authenticated', true);

        // Передаем управление следующему middleware
        return $next($request);
    }

    private function getUserIdFromToken(string $token): int
    {
        // Простая заглушка для демонстрации
        // В реальном приложении здесь должна быть проверка JWT или сессии
        return match ($token) {
            'admin-token' => 1,
            'user-token' => 2,
            default => 0
        };
    }
}
