<?php

declare(strict_types=1);

/**
 * Бенчмарк для сравнения производительности Matching vs OptimizedMatching
 *
 * Запуск: php benchmarks/MatchingBenchmark.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Route\RouteAnonymousFunc;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Components\matching\Matching;
use FaustVik\Router\Router\Components\matching\OptimizedMatching;

class MatchingBenchmark
{
    private const ITERATIONS = 1000;
    private const WARMUP_ITERATIONS = 100;

    public function run(): void
    {
        echo "=== Matching Performance Benchmark ===\n\n";

        $scenarios = [
            ['name' => '10 routes', 'count' => 10],
            ['name' => '50 routes', 'count' => 50],
            ['name' => '100 routes', 'count' => 100],
            ['name' => '500 routes', 'count' => 500],
            ['name' => '1000 routes', 'count' => 1000],
        ];

        foreach ($scenarios as $scenario) {
            $this->runScenario($scenario['name'], $scenario['count']);
        }
    }

    private function runScenario(string $name, int $routeCount): void
    {
        echo "--- {$name} ---\n";

        // Создаем маршруты
        $routes = $this->createRoutes($routeCount);

        // Тестируем разные позиции поиска
        $positions = [
            'first' => 0,
            'middle' => (int)($routeCount / 2),
            'last' => $routeCount - 1,
        ];

        foreach ($positions as $positionName => $routeIndex) {
            echo "  Position: {$positionName}\n";

            // Оригинальный Matching
            $originalTime = $this->benchmarkMatching(
                new Matching(),
                $routes,
                "/route{$routeIndex}"
            );

            // Оптимизированный Matching
            $optimizedTime = $this->benchmarkMatching(
                new OptimizedMatching(),
                $routes,
                "/route{$routeIndex}"
            );

            $improvement = $originalTime / $optimizedTime;

            echo "    Original:   " . $this->formatTime($originalTime) . "\n";
            echo "    Optimized:  " . $this->formatTime($optimizedTime) . "\n";
            echo "    Speedup:    {$improvement}x faster\n";
        }

        echo "\n";
    }

    private function benchmarkMatching(
        $matching,
        RoutesCollection $routes,
        string $uri
    ): float {
        // Сбрасываем индекс один раз перед warmup
        if ($matching instanceof OptimizedMatching) {
            $matching->resetIndex();
        }

        // Warmup - строит индекс при первом вызове
        for ($i = 0; $i < self::WARMUP_ITERATIONS; $i++) {
            try {
                $matching->match($uri, $routes);
            } catch (\Exception $e) {
                // Ignore
            }
        }

        // Actual benchmark - индекс уже построен
        $start = microtime(true);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            try {
                $matching->match($uri, $routes);
            } catch (\Exception $e) {
                // Ignore
            }
        }

        $end = microtime(true);

        return ($end - $start) / self::ITERATIONS;
    }

    private function createRoutes(int $count): RoutesCollection
    {
        $routes = new RoutesCollection();

        // Создаем микс статических и динамических маршрутов
        for ($i = 0; $i < $count; $i++) {
            if ($i % 3 === 0) {
                // Статический маршрут
                $routes->set(RouteAnonymousFunc::create(
                    "/route{$i}",
                    fn() => "Route {$i}",
                    ['GET']
                ));
            } else {
                // Динамический маршрут с параметром
                $routes->set(RouteAnonymousFunc::create(
                    "/route{$i}/{id}",
                    fn() => "Route {$i} with param",
                    ['GET']
                ));
            }
        }

        return $routes;
    }

    private function formatTime(float $seconds): string
    {
        if ($seconds < 0.000001) {
            return sprintf('%.2f ns', $seconds * 1_000_000_000);
        }
        if ($seconds < 0.001) {
            return sprintf('%.2f μs', $seconds * 1_000_000);
        }
        if ($seconds < 1) {
            return sprintf('%.2f ms', $seconds * 1000);
        }
        return sprintf('%.2f s', $seconds);
    }
}

// Запускаем бенчмарк
$benchmark = new MatchingBenchmark();
$benchmark->run();

