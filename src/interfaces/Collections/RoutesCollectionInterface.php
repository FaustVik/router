<?php
declare(strict_types=1);
namespace FaustVik\Router\interfaces\Collections;

use FaustVik\Router\interfaces\Routes\RouteInterface;

interface RoutesCollectionInterface
{
    public function set(RouteInterface ...$routes): void;

    /**
     * @return RouteInterface[]
     */
    public function get(): array;
}
