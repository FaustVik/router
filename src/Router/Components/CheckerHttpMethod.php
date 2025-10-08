<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components;

use FaustVik\Router\exceptions\NotAllowedHttpMethod;
use FaustVik\Router\interfaces\Router\Components\CheckHttpMethodInterface;

/**
 * HTTP method validator
 *
 * Validates if current HTTP method is allowed for route.
 * Throws exception if method not allowed.
 *
 * @package FaustVik\Router\Router\Components
 */
final class CheckerHttpMethod implements CheckHttpMethodInterface
{
    /**
     * Checks if HTTP method is allowed for route
     *
     * @param array<string> $methods Allowed HTTP methods
     * @param string|null $currentMethod Current HTTP method (if null, taken from $_SERVER)
     * @return void
     * @throws NotAllowedHttpMethod If method not allowed
     */
    public function isAllow(array $methods, ?string $currentMethod = null): void
    {
        if (empty($methods)) {
            return;
        }

        // Если метод не передан явно, берем из $_SERVER (обратная совместимость)
        if ($currentMethod === null) {
            $currentMethod = $this->getRequestMethod();
        } else {
            $currentMethod = strtoupper($currentMethod);
        }

        if (in_array($currentMethod, $methods, true)) {
            return;
        }

        throw new NotAllowedHttpMethod($currentMethod);
    }

    /**
     * Получает текущий HTTP метод запроса из $_SERVER
     *
     * @return string
     * @deprecated Используйте передачу метода в isAllow() напрямую
     */
    public function getRequestMethod(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        return strtoupper($method);
    }
}
