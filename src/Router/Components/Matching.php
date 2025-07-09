<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components;

use FaustVik\Router\exceptions\NoMatch;
use FaustVik\Router\interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\interfaces\Router\Components\MatchingRouteInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;

final class Matching implements MatchingRouteInterface
{
    /**
     * @throws NoMatch
     */
    public function match(string $uri, RoutesCollectionInterface $collections): MatchResult
    {
        foreach ($collections->get() as $route) {
            // Сначала проверяем точное совпадение (обратная совместимость)
            if (in_array($uri, [$route->alias(), $route->getRoute()], true)) {
                return new MatchResult($route, []);
            }

            // Проверяем совпадение с параметрами
            $matchResult = $this->matchWithParameters($uri, $route);
            if ($matchResult !== null) {
                return $matchResult;
            }
        }

        throw new NoMatch($uri);
    }
    private function matchWithParameters(string $uri, RouteInterface $route): ?MatchResult
    {
        // Проверяем основной маршрут
        $result = $this->matchPattern($uri, $route->getRoute());
        if ($result !== null) {
            return new MatchResult($route, $result);
        }

        // Проверяем алиас если он есть
        if ($route->alias() !== null) {
            $result = $this->matchPattern($uri, $route->alias());
            if ($result !== null) {
                return new MatchResult($route, $result);
            }
        }

        return null;
    }
    private function matchPattern(string $uri, string $pattern): ?array
    {
        // Разбиваем URI и паттерн на сегменты
        $uriSegments = $this->getSegments($uri);
        $patternSegments = $this->getSegments($pattern);

        // Количество сегментов должно совпадать
        if (count($uriSegments) !== count($patternSegments)) {
            return null;
        }

        $parameters = [];

        // Сравниваем каждый сегмент
        for ($i = 0; $i < count($patternSegments); $i++) {
            $patternSegment = $patternSegments[$i];
            $uriSegment = $uriSegments[$i];

            // Если сегмент в фигурных скобках - это параметр
            if ($this->isParameter($patternSegment)) {
                $parameterName = $this->getParameterName($patternSegment);
                $parameters[$parameterName] = $uriSegment;
            } else {
                // Иначе должно быть точное совпадение
                if ($patternSegment !== $uriSegment) {
                    return null;
                }
            }
        }

        return $parameters;
    }
    private function getSegments(string $path): array
    {
        // Используем array_values для пересоздания индексов
        return array_values(array_filter(explode('/', $path), fn($segment) => $segment !== ''));
    }
    private function isParameter(string $segment): bool
    {
        return str_starts_with($segment, '{') && str_ends_with($segment, '}');
    }
    private function getParameterName(string $segment): string
    {
        return substr($segment, 1, -1);
    }
}
