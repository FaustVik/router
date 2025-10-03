<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components;

use FaustVik\Router\exceptions\NotAllowedHttpMethod;
use FaustVik\Router\interfaces\Router\Components\CheckHttpMethodInterface;

final class CheckerHttpMethod implements CheckHttpMethodInterface
{
    /**
     * @param array<string> $methods
     * @return void
     * @throws NotAllowedHttpMethod
     */
    public function isAllow(array $methods): void
    {
        if (empty($methods)) {
            return;
        }

        $currentMethod = $this->getRequestMethod();

        if (in_array($currentMethod, $methods, true)) {
            return;
        }

        throw new NotAllowedHttpMethod($currentMethod);
    }

    public function getRequestMethod(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        return strtoupper($method);
    }
}
