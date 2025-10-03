<?php

declare(strict_types=1);

namespace FaustVik\Router\interfaces\Router\Components;

use FaustVik\Router\exceptions\NotAllowedHttpMethod;

interface CheckHttpMethodInterface
{
    /**
     * Adding routes
     *
     * @param array $methods
     *
     * @return boolean
     *
     * @throws NotAllowedHttpMethod
     */
    public static function isAllow(array $methods): bool;

    /**
     * Get which method was used
     *
     * @return string
     */
    public static function getRequestMethod(): string;
}
