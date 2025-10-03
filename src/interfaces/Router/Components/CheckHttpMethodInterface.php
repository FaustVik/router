<?php

declare(strict_types=1);

namespace FaustVik\Router\interfaces\Router\Components;

use FaustVik\Router\exceptions\NotAllowedHttpMethod;

interface CheckHttpMethodInterface
{
    /**
     * Проверяет, разрешён ли текущий HTTP метод для маршрута
     *
     * @param array<string> $methods Разрешённые HTTP методы
     * @return void
     * @throws NotAllowedHttpMethod
     */
    public function isAllow(array $methods): void;

    /**
     * Получает текущий HTTP метод запроса
     *
     * @return string
     */
    public function getRequestMethod(): string;
}
