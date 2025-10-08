<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components;

use FaustVik\Router\exceptions\NotAllowedHttpMethod;
use FaustVik\Router\interfaces\Router\Components\CheckHttpMethodInterface;

final class CheckerHttpMethod implements CheckHttpMethodInterface
{
    /**
     * Проверяет, разрешён ли HTTP метод для маршрута
     *
     * @param array<string> $methods Разрешённые HTTP методы
     * @param string|null $currentMethod Текущий HTTP метод (если null, берется из $_SERVER)
     * @return void
     * @throws NotAllowedHttpMethod
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
