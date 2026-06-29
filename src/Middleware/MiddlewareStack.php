<?php

declare(strict_types=1);

namespace FaustVik\Router\Middleware;

use ArgumentCountError;
use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Interfaces\DI\RouterContainerInterface;
use FaustVik\Router\Interfaces\Middleware\MiddlewareInterface;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Middleware Stack for sequential request processing
 *
 * Implements Chain of Responsibility pattern for processing
 * HTTP requests through a sequence of middleware.
 *
 * Each middleware can:
 * - Process request before passing to next
 * - Pass control to next middleware
 * - Process response after next middleware execution
 * - Break chain and return own response
 *
 * Usage example:
 * ```php
 * $stack = new MiddlewareStack(function($request) {
 *     return Response::json(['message' => 'Hello']);
 * });
 *
 * $stack
 *     ->add(new LoggingMiddleware())
 *     ->add(new CorsMiddleware())
 *     ->add(new AuthMiddleware());
 *
 * $response = $stack->execute($request);
 * ```
 *
 * Example with DI container for middleware with dependencies:
 * ```php
 * $stack = new MiddlewareStack($finalHandler, $container);
 * $stack->addFromArray([
 *     RateLimitMiddleware::class, // Will be resolved through container
 *     AuthMiddleware::class
 * ]);
 * ```
 *
 * @package FaustVik\Router\Middleware
 */
final class MiddlewareStack
{
    /**
     * @var array<MiddlewareInterface> Массив зарегистрированных middleware
     */
    private array $middleware = [];

    /**
     * @var callable Финальный обработчик, вызывается когда все middleware выполнены
     */
    private $finalHandler;

    /**
     * @var RouterContainerInterface|null DI контейнер для разрешения зависимостей middleware
     */
    private ?RouterContainerInterface $container = null;

    /**
     * @param callable $finalHandler Функция обработки запроса: fn(Request): Response
     * @param RouterContainerInterface|null $container Опциональный DI контейнер для middleware с зависимостями
     */
    public function __construct(callable $finalHandler, ?RouterContainerInterface $container = null)
    {
        $this->finalHandler = $finalHandler;
        $this->container = $container;
    }

    /**
     * Добавляет middleware в конец стека
     *
     * @param MiddlewareInterface $middleware Экземпляр middleware
     * @return self Для fluent interface
     */
    public function add(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Добавляет middleware в начало стека (выполнится первым)
     *
     * @param MiddlewareInterface $middleware Экземпляр middleware
     * @return self Для fluent interface
     */
    public function prepend(MiddlewareInterface $middleware): self
    {
        array_unshift($this->middleware, $middleware);
        return $this;
    }

    /**
     * Добавляет несколько middleware из массива
     *
     * Массив может содержать:
     * - Экземпляры MiddlewareInterface
     * - Имена классов middleware (будут разрешены через DI контейнер или созданы напрямую)
     *
     * Для middleware с зависимостями в конструкторе необходимо:
     * 1. Передать DI контейнер в конструктор MiddlewareStack
     * 2. Зарегистрировать middleware в контейнере
     * 3. Передать имя класса в массиве
     *
     * Для простых middleware без зависимостей можно просто передать имя класса.
     *
     * @param array<MiddlewareInterface|string|callable> $middleware
     * @return self Для fluent interface
     * @throws RuntimeException|InvalidArgumentException If middleware is invalid or cannot be resolved
     */
    public function addFromArray(array $middleware): self
    {
        foreach ($middleware as $item) {
            if (is_string($item)) {
                // Если это строка с именем класса, пытаемся его разрешить
                $item = $this->resolveMiddleware($item);
            } elseif (is_callable($item)) {
                // Если это callable, оборачиваем в анонимный middleware
                $item = new class ($item) implements MiddlewareInterface {
                    /**
                     * @param callable(Request, callable): Response $handler
                     */
                    public function __construct(private $handler)
                    {
                    }

                    public function handle(Request $request, callable $next): Response
                    {
                        return ($this->handler)($request, $next);
                    }
                };
            }

            if (!$item instanceof MiddlewareInterface) { // @phpstan-ignore instanceof.alwaysTrue
                throw new InvalidArgumentException(
                    sprintf(
                        'Middleware must implement MiddlewareInterface, got: %s',
                        get_debug_type($item)
                    )
                );
            }

            $this->add($item);
        }
        return $this;
    }

    /**
     * Разрешает middleware по имени класса
     *
     * Алгоритм разрешения:
     * 1. Если передан DI контейнер и он может разрешить класс - используем контейнер
     * 2. Иначе пытаемся создать экземпляр напрямую (для middleware без зависимостей)
     * 3. Если конструктор требует параметры - выбрасываем понятную ошибку
     *
     * @param string $className Имя класса middleware
     * @return MiddlewareInterface Разрешенный middleware
     * @throws RuntimeException Если не удалось разрешить middleware
     */
    private function resolveMiddleware(string $className): MiddlewareInterface
    {
        // Проверяем существование класса
        if (!class_exists($className)) {
            throw new RuntimeException(
                sprintf('Middleware class not found: %s', $className)
            );
        }

        // Пытаемся разрешить через DI контейнер
        if ($this->container !== null && $this->container->canResolve($className)) {
            try {
                $instance = $this->container->resolve($className);

                if (!$instance instanceof MiddlewareInterface) {
                    throw new RuntimeException(
                        sprintf(
                            'Container resolved %s, but it does not implement MiddlewareInterface',
                            $className
                        )
                    );
                }

                return $instance;
            } catch (Throwable $e) {
                throw new RuntimeException(
                    sprintf(
                        'Failed to resolve middleware %s from DI container: %s',
                        $className,
                        $e->getMessage()
                    ),
                    0,
                    $e
                );
            }
        }

        // Fallback: пытаемся создать напрямую (для middleware без зависимостей)
        try {
            $instance = new $className();

            if (!$instance instanceof MiddlewareInterface) {
                throw new RuntimeException(
                    sprintf(
                        'Class %s does not implement MiddlewareInterface',
                        $className
                    )
                );
            }

            return $instance;
        } catch (ArgumentCountError $e) {
            // Конструктор требует параметры, но DI контейнер не доступен
            throw new RuntimeException(
                sprintf(
                    'Middleware %s requires constructor dependencies, but no DI container is available. '
                    . 'Either register it in the DI container and pass the container to MiddlewareStack, '
                    . 'or pass an instance of the middleware instead of class name.',
                    $className
                ),
                0,
                $e
            );
        } catch (Throwable $e) {
            throw new RuntimeException(
                sprintf(
                    'Failed to instantiate middleware %s: %s',
                    $className,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }
    }

    /**
     * Удаляет middleware по индексу
     *
     * @param int $index Индекс middleware в стеке (начиная с 0)
     * @return self Для fluent interface
     */
    public function remove(int $index): self
    {
        if (isset($this->middleware[$index])) {
            array_splice($this->middleware, $index, 1);
        }
        return $this;
    }

    /**
     * Удаляет все middleware указанного класса
     *
     * @param class-string<MiddlewareInterface> $className Имя класса middleware
     * @return self Для fluent interface
     */
    public function removeByClass(string $className): self
    {
        $this->middleware = array_values(
            array_filter(
                $this->middleware,
                fn ($mw) => !($mw instanceof $className)
            )
        );
        return $this;
    }

    /**
     * Выполняет стек middleware и возвращает ответ
     *
     * Запускает цепочку middleware, где каждый middleware получает запрос
     * и функцию для вызова следующего middleware в цепочке.
     *
     * @param Request $request HTTP запрос для обработки
     * @return Response HTTP ответ после прохождения через все middleware
     */
    public function execute(Request $request): Response
    {
        $index = 0;

        $next = function (Request $request) use (&$next, &$index): Response {
            // Если все middleware выполнены, вызываем финальный обработчик
            if ($index >= count($this->middleware)) {
                return ($this->finalHandler)($request);
            }

            // Получаем следующий middleware и увеличиваем индекс
            $middleware = $this->middleware[$index++];

            // Вызываем middleware с запросом и функцией next
            return $middleware->handle($request, $next);
        };

        return $next($request);
    }

    /**
     * Возвращает количество middleware в стеке
     *
     * @return int Количество зарегистрированных middleware
     */
    public function count(): int
    {
        return count($this->middleware);
    }

    /**
     * Проверяет, пуст ли стек middleware
     *
     * @return bool true если стек пуст
     */
    public function isEmpty(): bool
    {
        return empty($this->middleware);
    }

    /**
     * Очищает весь стек middleware
     *
     * @return self Для fluent interface
     */
    public function clear(): self
    {
        $this->middleware = [];
        return $this;
    }

    /**
     * Возвращает все middleware в стеке
     *
     * @return array<MiddlewareInterface> Массив middleware
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Получает middleware по индексу
     *
     * @param int $index Индекс middleware (начиная с 0)
     * @return MiddlewareInterface|null Middleware или null если индекс неверный
     */
    public function getAt(int $index): ?MiddlewareInterface
    {
        return $this->middleware[$index] ?? null;
    }

    /**
     * Проверяет наличие middleware указанного класса
     *
     * @param class-string<MiddlewareInterface> $className Имя класса middleware
     * @return bool true если middleware присутствует в стеке
     */
    public function has(string $className): bool
    {
        foreach ($this->middleware as $mw) {
            if ($mw instanceof $className) {
                return true;
            }
        }
        return false;
    }

    /**
     * Находит первый middleware указанного класса
     *
     * @template T of MiddlewareInterface
     * @param class-string<T> $className Имя класса middleware
     * @return T|null Найденный middleware или null
     */
    public function findByClass(string $className): ?MiddlewareInterface
    {
        foreach ($this->middleware as $mw) {
            if ($mw instanceof $className) {
                return $mw;
            }
        }
        return null;
    }
}
