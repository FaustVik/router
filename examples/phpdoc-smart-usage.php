<?php

/**
 * Пример разумного использования PHPDoc в PHP 8.1+
 *
 * Демонстрирует, где PHPDoc нужен, а где избыточен в контексте строгой типизации
 */

declare(strict_types=1);

use FaustVik\Router\Interfaces\Middleware\MiddlewareInterface;
use FaustVik\Router\Interfaces\Routes\RouteInterface;

/**
 * Пример класса с разумным использованием PHPDoc
 *
 * В этом классе показано, где PHPDoc полезен, а где избыточен
 */
class SmartPhpDocExample
{
    // ❌ НЕ НУЖЕН PHPDoc для простых свойств с явной типизацией
    private string $name;
    private int $count = 0;
    private ?object $container = null;

    // ✅ ПОЛЕЗЕН PHPDoc для массивов с типизированными элементами
    /** @var RouteInterface[] */
    private array $routes = [];

    /** @var MiddlewareInterface[] */
    private array $middleware = [];

    /** @var array<string, mixed> */
    private array $config = [];

    // ❌ НЕ НУЖЕН PHPDoc для простого конструктора

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    // ❌ НЕ НУЖЕН PHPDoc для простых геттеров/сеттеров

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    // ✅ ПОЛЕЗЕН PHPDoc для массивов с типизированными элементами

    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @param RouteInterface[] $routes
     */
    public function setRoutes(array $routes): void
    {
        $this->routes = $routes;
    }

    // ✅ ПОЛЕЗЕН PHPDoc для методов с исключениями

    /**
     * @throws InvalidArgumentException Если маршрут невалидный
     * @throws RuntimeException Если маршрут уже существует
     */
    public function addRoute(RouteInterface $route): void
    {
        if (!$route->getRoute()) {
            throw new InvalidArgumentException('Route cannot be empty');
        }

        if (isset($this->routes[$route->getRoute()])) {
            throw new RuntimeException('Route already exists');
        }

        $this->routes[$route->getRoute()] = $route;
    }

    // ✅ ПОЛЕЗЕН PHPDoc для сложного поведения

    /**
     * Находит маршрут по паттерну
     *
     * Поддерживает поиск как по точному совпадению, так и по regex паттерну.
     * При поиске по regex используется preg_match с паттерном.
     *
     * @param string $pattern Паттерн для поиска (может быть regex)
     * @param bool $useRegex Использовать ли regex поиск
     * @return RouteInterface|null Найденный маршрут или null
     */
    public function findRoute(string $pattern, bool $useRegex = false): ?RouteInterface
    {
        if (!$useRegex) {
            return $this->routes[$pattern] ?? null;
        }

        foreach ($this->routes as $route) {
            if (preg_match($pattern, $route->getRoute())) {
                return $route;
            }
        }

        return null;
    }

    // ✅ ПОЛЕЗЕН PHPDoc для mixed типов

    /**
     * @param mixed $value
     * @return mixed
     */
    public function processValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return strtoupper($value);
        }

        if (is_array($value)) {
            return array_map('strtoupper', $value);
        }

        return $value;
    }

    // ✅ ПОЛЕЗЕН PHPDoc для callable типов

    /**
     * @param callable $callback
     * @param array<string, mixed> $parameters
     */
    public function executeCallback(callable $callback, array $parameters = []): void
    {
        call_user_func_array($callback, $parameters);
    }

    // ❌ НЕ НУЖЕН PHPDoc для простых методов с очевидными типами
    public function increment(): void
    {
        $this->count++;
    }

    public function reset(): void
    {
        $this->count = 0;
        $this->routes = [];
        $this->middleware = [];
    }

    public function isEmpty(): bool
    {
        return empty($this->routes);
    }

    // ✅ ПОЛЕЗЕН PHPDoc для методов с комплексными возвращаемыми типами

    /**
     * @return array{routes: RouteInterface[], middleware: MiddlewareInterface[], config: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'routes' => $this->routes,
            'middleware' => $this->middleware,
            'config' => $this->config,
        ];
    }

    // ✅ ПОЛЕЗЕН PHPDoc для дженерик-подобных методов

    /**
     * @template T
     * @param class-string<T> $className
     * @return T
     */
    public function createInstance(string $className): object
    {
        return new $className();
    }
}

// ========================================
// РЕЗЮМЕ: Когда использовать PHPDoc
// ========================================

/**
 * ✅ PHPDoc ПОЛЕЗЕН для:
 *
 * 1. Массивов с типизированными элементами:
 *    - @param mixed $value
 *    - @param callable $callback
 *
 * 4. Сложного поведения методов:
 *    - Описание алгоритма
 *    - Примеры использования
 *    - Особенности работы
 *
 * 5. Комплексных типов:
 *    - @param class-string<T> $className
 *    - @return User[]
 *
 * 2. Методов с исключениями:
 *    - @return mixed
 *    - @return array{name: string, age: int}
 *    - @param array<string, RouteInterface> $routes
 *
 * 6. Дженерик-подобных конструкций:
 *    - @template T
 *    - @return T
 *@throws ValidationException
 *    - @throws RuntimeException
 *
 * 3. Сложных типов:
 *    - @var RouteInterface[]
 *    - @var array<string, mixed>
 *    -/

/**
 * ❌ PHPDoc НЕ НУЖЕН для:
 *
 * 1. Простых свойств с явной типизацией:
 *    - private string $name;
 *    - private int $count = 0;
 *    - private ?object $container = null;
 *
 * 2. Простых геттеров/сеттеров:
 *    - public function getName(): string
 *    - public function setName(string $name): void
 *
 * 3. Конструкторов без сложной логики:
 *    - public function __construct(string $name)
 *
 * 4. Методов с очевидными типами:
 *    - public function increment(): void
 *    - public function isEmpty(): bool
 *
 * 5. Простых методов интерфейсов:
 *    - public function process(): bool
 *    - public function getId(): int
 */
echo "Пример разумного использования PHPDoc завершен!\n";
