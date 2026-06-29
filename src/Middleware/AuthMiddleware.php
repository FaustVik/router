<?php

declare(strict_types=1);

namespace FaustVik\Router\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Interfaces\Middleware\MiddlewareInterface;

/**
 * Middleware for basic HTTP authentication
 *
 * Checks presence and validity of Bearer token in Authorization header.
 * Adds user information to request attributes on successful authentication.
 *
 * Requires a token validation function to be provided.
 *
 * Usage example:
 * ```php
 * $auth = new AuthMiddleware(function(string $token): ?array {
 *     // Your token validation logic (JWT, database, etc.)
 *     $user = $userRepository->findByToken($token);
 *     return $user ? $user->toArray() : null;
 * });
 * ```
 *
 * @package FaustVik\Router\Middleware
 */
final class AuthMiddleware implements MiddlewareInterface
{
    private const BEARER_PREFIX = 'Bearer ';
    private const BEARER_PREFIX_LENGTH = 7;

    /**
     * @var callable Валидатор токена (принимает строку токена, возвращает user data или null)
     */
    private $tokenValidator;

    /**
     * @param callable $tokenValidator Функция проверки токена: fn(string $token): ?array
     */
    public function __construct(callable $tokenValidator)
    {
        $this->tokenValidator = $tokenValidator;
    }

    /**
     * Обрабатывает входящий запрос
     *
     * Проверяет Authorization заголовок на наличие Bearer токена,
     * валидирует токен и добавляет информацию о пользователе в запрос.
     *
     * @param Request $request HTTP запрос
     * @param callable $next Следующий middleware в цепочке
     * @return Response HTTP ответ
     */
    public function handle(Request $request, callable $next): Response
    {
        $authHeader = $request->getHeader('Authorization');

        if (!$authHeader || !is_string($authHeader)) {
            return $this->unauthorizedResponse('Authorization header is required');
        }

        if (!str_starts_with($authHeader, self::BEARER_PREFIX)) {
            return $this->unauthorizedResponse('Invalid authorization format. Use: Bearer <token>');
        }

        $token = substr($authHeader, self::BEARER_PREFIX_LENGTH);

        if (empty($token)) {
            return $this->unauthorizedResponse('Token is empty');
        }

        // Валидация токена
        $userData = $this->validateToken($token);
        if ($userData === null) {
            return $this->unauthorizedResponse('Invalid or expired token');
        }

        // Добавляем информацию о пользователе в запрос
        $request = $request
            ->withAttribute('authenticated', true)
            ->withAttribute('user', $userData)
            ->withAttribute('token', $token);

        return $next($request);
    }

    /**
     * Валидирует токен и возвращает данные пользователя
     *
     * @param string $token Токен для проверки
     * @return array<string, mixed>|null Данные пользователя или null если токен невалиден
     */
    private function validateToken(string $token): ?array
    {
        $result = ($this->tokenValidator)($token);
        return is_array($result) ? $result : null;
    }

    /**
     * Создает JSON ответ с ошибкой 401
     *
     * @param string $message Сообщение об ошибке
     * @return Response
     */
    private function unauthorizedResponse(string $message): Response
    {
        return Response::json(
            [
                'error' => 'Unauthorized',
                'message' => $message,
            ],
            401,
            ['WWW-Authenticate' => 'Bearer realm="API"']
        );
    }
}
