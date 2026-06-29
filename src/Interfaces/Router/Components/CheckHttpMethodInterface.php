<?php

declare(strict_types=1);

namespace FaustVik\Router\Interfaces\Router\Components;

use FaustVik\Router\exceptions\NotAllowedHttpMethod;

interface CheckHttpMethodInterface
{
    /**
     * Проверяет, разрешён ли HTTP метод для маршрута
     *
     * @param array<string> $methods Разрешённые HTTP методы
     * @param string|null $currentMethod Текущий HTTP метод (если null, берется из $_SERVER)
     * @return void
     * @throws NotAllowedHttpMethod
     */
    public function isAllow(array $methods, ?string $currentMethod = null): void;

    /**
     * Получает текущий HTTP метод запроса из $_SERVER
     *
     * @return string
     * @deprecated Используйте передачу метода в isAllow() напрямую
     */
    public function getRequestMethod(): string;
}
