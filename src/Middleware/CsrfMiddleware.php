<?php

declare(strict_types=1);

namespace FaustVik\Router\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Interfaces\Middleware\MiddlewareInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Middleware for CSRF attack protection (Cross-Site Request Forgery)
 *
 * Generates and validates CSRF tokens to protect against cross-site request forgery.
 * Token is validated for all mutating methods: POST, PUT, PATCH, DELETE.
 *
 * Token can be passed as:
 * - Form field: _csrf_token
 * - Header: X-CSRF-Token
 * - Query parameter: _csrf_token
 *
 * Uses hash_equals() for safe token comparison (timing attack protection).
 *
 * Usage example:
 * ```php
 * // In middleware chain
 * $csrf = new CsrfMiddleware(
 *     tokenLength: 32,
 *     sessionKey: '_csrf_token',
 *     excludePaths: ['/api/webhook'] // Exclude webhook endpoints
 * );
 *
 * // In form
 * <input type="hidden" name="_csrf_token" value="<?= CsrfMiddleware::getToken() ?>">
 *
 * // Or in AJAX request
 * headers: { 'X-CSRF-Token': '<?= CsrfMiddleware::getToken() ?>' }
 * ```
 *
 * @package FaustVik\Router\Middleware
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    private const HTTP_METHODS_TO_CHECK = ['POST', 'PUT', 'PATCH', 'DELETE'];
    private const TOKEN_FIELD_NAME = '_csrf_token';
    private const TOKEN_HEADER_NAME = 'X-CSRF-Token';
    private const DEFAULT_SESSION_KEY = '_csrf_token';
    private const DEFAULT_TOKEN_LENGTH = 32;

    private string $sessionKey;

    /**
     * @param int<1, max> $tokenLength Token length in bytes (will be doubled in hex)
     * @param string|null $sessionKey Key for storing token in session
     * @param array<int, string> $excludePaths Paths that don't require CSRF check
     * @throws InvalidArgumentException If token length is less than 1
     */
    public function __construct(
        private readonly int $tokenLength = self::DEFAULT_TOKEN_LENGTH,
        ?string $sessionKey = null,
        private readonly array $excludePaths = []
    ) {
        // Validation is redundant due to PHPDoc type, but kept for runtime safety
        // @phpstan-ignore-next-line
        if ($tokenLength < 1) {
            throw new InvalidArgumentException('Token length must be at least 1');
        }

        $this->sessionKey = $sessionKey ?? self::DEFAULT_SESSION_KEY;
    }

    /**
     * Обрабатывает входящий запрос с проверкой CSRF токена
     *
     * Генерирует токен если его нет, проверяет токен для изменяющих методов.
     * Возвращает 419 Page Expired если токен не валиден.
     *
     * @param Request $request HTTP запрос
     * @param callable $next Следующий middleware в цепочке
     * @return Response HTTP ответ
     * @throws RuntimeException Если не удалось инициализировать сессию
     */
    public function handle(Request $request, callable $next): Response
    {
        $this->ensureSessionStarted();

        // Генерируем токен если его нет
        $this->ensureTokenExists();

        // Проверяем, не исключен ли текущий путь
        if ($this->isPathExcluded($request->getPath())) {
            return $next($request);
        }

        // Проверяем токен для изменяющих методов
        if ($this->shouldVerifyToken($request)) {
            $token = $this->extractToken($request);

            if (!$this->isValidToken($token)) {
                return $this->buildTokenMismatchResponse();
            }
        }

        // Добавляем токен в атрибуты запроса для использования в приложении
        $request = $request->withAttribute('csrf_token', $this->getStoredToken());

        return $next($request);
    }

    /**
     * Убеждается что PHP сессия инициализирована
     *
     * @throws RuntimeException Если не удалось запустить сессию
     */
    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (!session_start()) {
                throw new RuntimeException('Failed to start session for CSRF protection');
            }
        }
    }

    /**
     * Генерирует новый токен если он отсутствует в сессии
     */
    private function ensureTokenExists(): void
    {
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = $this->generateToken();
        }
    }

    /**
     * Генерирует криптографически безопасный случайный токен
     *
     * @return string Hex-представление токена
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes($this->tokenLength));
    }

    /**
     * Определяет нужно ли проверять токен для данного запроса
     *
     * @param Request $request HTTP запрос
     * @return bool True если токен нужно проверить
     */
    private function shouldVerifyToken(Request $request): bool
    {
        return in_array($request->getMethod(), self::HTTP_METHODS_TO_CHECK, true);
    }

    /**
     * Проверяет исключен ли путь из CSRF проверки
     *
     * @param string $path Путь запроса
     * @return bool True если путь исключен
     */
    private function isPathExcluded(string $path): bool
    {
        foreach ($this->excludePaths as $excludePath) {
            if ($path === $excludePath || str_starts_with($path, $excludePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Извлекает CSRF токен из запроса
     *
     * Проверяет в следующем порядке:
     * 1. Поле формы _csrf_token
     * 2. Заголовок X-CSRF-Token
     * 3. Query параметр _csrf_token
     *
     * @param Request $request HTTP запрос
     * @return string|null Токен или null если не найден
     */
    private function extractToken(Request $request): ?string
    {
        // Проверяем POST данные
        $token = $request->input(self::TOKEN_FIELD_NAME);
        if ($token !== null && (is_string($token) || is_numeric($token))) {
            return (string) $token;
        }

        // Проверяем заголовки
        $token = $request->getHeader(self::TOKEN_HEADER_NAME);
        if ($token !== null && (is_string($token) || is_numeric($token))) {
            return (string) $token;
        }

        // Проверяем query параметры
        $query = $request->getQuery();
        if (isset($query[self::TOKEN_FIELD_NAME])) {
            $queryToken = $query[self::TOKEN_FIELD_NAME];
            if (is_string($queryToken) || is_numeric($queryToken)) {
                return (string) $queryToken;
            }
        }

        return null;
    }

    /**
     * Проверяет валидность токена
     *
     * Использует hash_equals() для защиты от timing attacks
     *
     * @param string|null $token Токен для проверки
     * @return bool True если токен валиден
     */
    private function isValidToken(?string $token): bool
    {
        $sessionToken = $this->getStoredToken();

        if ($token === null || $sessionToken === null) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Получает токен из сессии
     *
     * @return string|null Токен или null если не найден
     */
    private function getStoredToken(): ?string
    {
        return $_SESSION[$this->sessionKey] ?? null;
    }

    /**
     * Создает JSON ответ при несовпадении токена
     *
     * Возвращает статус 419 Page Expired (Laravel convention)
     *
     * @return Response
     */
    private function buildTokenMismatchResponse(): Response
    {
        return Response::json(
            data: [
                'error' => 'CSRF Token Mismatch',
                'message' => 'CSRF token validation failed. Please refresh the page and try again.',
            ],
            statusCode: 419,
            headers: [
                'X-CSRF-Protection' => 'token-mismatch',
            ]
        );
    }

    /**
     * Получает текущий CSRF токен (статический метод для использования в шаблонах)
     *
     * @param string|null $sessionKey Кастомный ключ сессии
     * @return string CSRF токен или пустая строка если сессия не инициализирована
     */
    public static function getToken(?string $sessionKey = null): string
    {
        $key = $sessionKey ?? self::DEFAULT_SESSION_KEY;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = bin2hex(random_bytes(self::DEFAULT_TOKEN_LENGTH));
        }

        return $_SESSION[$key] ?? '';
    }

    /**
     * Регенерирует CSRF токен
     *
     * Полезно после входа/выхода пользователя для повышения безопасности
     *
     * @return string Новый токен
     */
    public function regenerateToken(): string
    {
        $this->ensureSessionStarted();
        $newToken = $this->generateToken();
        $_SESSION[$this->sessionKey] = $newToken;
        return $newToken;
    }

    /**
     * Получает HTML input элемент с CSRF токеном
     *
     * Удобный метод для вставки в формы
     *
     * @return string HTML код скрытого поля с токеном
     */
    public static function getTokenField(): string
    {
        $token = self::getToken();
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars(self::TOKEN_FIELD_NAME, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Получает meta тег с CSRF токеном для AJAX запросов
     *
     * Использование в JavaScript:
     * ```javascript
     * const token = document.querySelector('meta[name="csrf-token"]').content;
     * ```
     *
     * @return string HTML код meta тега с токеном
     */
    public static function getTokenMeta(): string
    {
        $token = self::getToken();
        return sprintf(
            '<meta name="csrf-token" content="%s">',
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }
}
