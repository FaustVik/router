<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components\matching;

use FaustVik\Router\Exceptions\NoMatch;
use FaustVik\Router\Interfaces\Collections\RoutesCollectionInterface;
use FaustVik\Router\Interfaces\Router\Components\MatchingRouteInterface;
use FaustVik\Router\Interfaces\Routes\RouteInterface;

/**
 * Basic route matching implementation
 *
 * Performs route matching by comparing URI against route patterns.
 * Supports:
 * - Static routes
 * - Dynamic parameters {param}
 * - Optional parameters {param?}
 * - Parameter constraints
 * - Route aliases
 *
 * @package FaustVik\Router\Router\Components\matching
 */
final class Matching implements MatchingRouteInterface
{
    /**
     * Matches URI against routes collection
     *
     * @param string $uri URI to match
     * @param RoutesCollectionInterface|null $collections Routes collection
     * @return MatchResult Match result with route and parameters
     * @throws NoMatch If no matching route found
     */
    public function match(string $uri, ?RoutesCollectionInterface $collections): MatchResult
    {
        if ($collections === null) {
            throw new NoMatch($uri);
        }

        foreach ($collections->get() as $route) {
            if ($uri === $route->getRoute() || $uri === $route->alias()) {
                return new MatchResult($route, []);
            }

            $matchResult = $this->matchWithParameters($uri, $route);
            if ($matchResult !== null) {
                return $matchResult;
            }
        }

        throw new NoMatch($uri);
    }

    private function matchWithParameters(string $uri, RouteInterface $route): ?MatchResult
    {
        $result = $this->matchPattern($uri, $route->getRoute());
        if ($result !== null && $this->validateConstraints($result, $route)) {
            return new MatchResult($route, $result);
        }

        if ($route->alias() !== null) {
            $result = $this->matchPattern($uri, $route->alias());
            if ($result !== null && $this->validateConstraints($result, $route)) {
                return new MatchResult($route, $result);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function validateConstraints(array $parameters, RouteInterface $route): bool
    {
        $constraints = $route->getConstraints();
        if ($constraints === []) {
            return true;
        }

        foreach ($constraints as $param => $pattern) {
            if (isset($parameters[$param])) {
                $regexp = '#^' . $pattern . '$#';
                if (preg_match($regexp, (string) $parameters[$param]) !== 1) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function matchPattern(string $uri, string $pattern): ?array
    {
        $uriSegments = $this->getSegments($uri);
        $patternSegments = $this->getSegments($pattern);

        if (count($uriSegments) !== count($patternSegments)) {
            return null;
        }

        $parameters = [];

        for ($i = 0; $i < count($patternSegments); $i++) {
            $patternSegment = $patternSegments[$i];
            $uriSegment = $uriSegments[$i];

            if ($this->isParameter($patternSegment)) {
                $parameterName = $this->getParameterName($patternSegment);
                $parameters[$parameterName] = $uriSegment;
            } elseif ($patternSegment !== $uriSegment) {
                return null;
            }
        }

        return $parameters;
    }

    /**
     * @return array<int, string>
     */
    private function getSegments(string $path): array
    {
        return array_values(array_filter(explode('/', $path), fn ($segment) => $segment !== ''));
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
