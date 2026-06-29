<?php

declare(strict_types=1);

namespace FaustVik\Router\Interfaces\Collections;

use FaustVik\Router\Interfaces\Routes\RouteInterface;

interface RoutesCollectionInterface
{
    public function set(RouteInterface ...$routes): void;

    /**
     * @return RouteInterface[]
     */
    public function get(): array;
}
