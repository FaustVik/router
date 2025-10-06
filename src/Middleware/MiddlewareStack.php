<?php

declare(strict_types=1);

namespace FaustVik\Router\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\interfaces\Middleware\MiddlewareInterface;

/**
 * Стек Middleware для последовательной обработки запросов
 *
 * Реализует паттерн "цепочка обязанностей" (Chain of Responsibility)
 * для обработки HTTP запросов через последовательность middleware.
 *
 * Каждый middleware может:
 * - Обработать запрос перед передачей следующему
 * - Передать управление следующему middleware
 * - Обработать ответ после выполнения следующих middleware
 * - Прервать цепочку и вернуть собственный ответ
 *
 * Пример использования:
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
     * @param callable $finalHandler Функция обработки запроса: fn(Request): Response
     */
    public function __construct(callable $finalHandler)
    {
        $this->finalHandler = $finalHandler;
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
     * - Имена классов middleware (будут автоматически созданы)
     *
     * @param array<MiddlewareInterface|class-string<MiddlewareInterface>> $middleware
     * @return self Для fluent interface
     */
    public function addFromArray(array $middleware): self
    {
        foreach ($middleware as $item) {
            if (is_string($item) && class_exists($item)) {
                // Если это строка с именем класса, создаем экземпляр
                $item = new $item();
            }

            if ($item instanceof MiddlewareInterface) {
                $this->add($item);
            }
        }
        return $this;
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
                fn($mw) => !($mw instanceof $className)
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
