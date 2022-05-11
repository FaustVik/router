<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components;

use FaustVik\Router\exceptions\NotAllowedHttpMethod;
use FaustVik\Router\interfaces\Router\Components\CheckHttpMethodInterface;

final class CheckerHttpMethod implements CheckHttpMethodInterface
{
    /**
     * @param array $methods
     *
     * @return bool
     * @throws NotAllowedHttpMethod
     */
    public static function isAllow(array $methods = []): bool
    {
        if (empty($methods)) {
            return true;
        }

        if (!in_array(self::getRequestMethod(), $methods, true)) {
            throw new NotAllowedHttpMethod(self::getRequestMethod());
        }

        return true;
    }

    public static function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }
}
