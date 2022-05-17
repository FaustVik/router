<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use FaustVik\Router\interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;

final class RoutesCollection implements RoutesCollectionInterface
{
    /**@var RouteInterface[] $collections */
    private array $collections = [];

    public function set(RouteInterface ...$routes): void
    {
        foreach ($routes as $route){
            $this->collections[] = $route;
        }
    }

    /**
     * @return RouteInterface[]
     */
    public function get(): array
    {
        return $this->collections;
    }
}
