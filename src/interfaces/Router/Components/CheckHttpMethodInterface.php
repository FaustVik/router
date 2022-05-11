<?php

namespace FaustVik\Router\interfaces\Router\Components;

use FaustVik\Router\exceptions\NotAllowedHttpMethod;

interface CheckHttpMethodInterface
{
    /**
     * @param array $methods
     *
     * @return bool
     *
     * @throws NotAllowedHttpMethod
     */
    public static function isAllow(array $methods = []): bool;

    public static function getRequestMethod(): string;
}
