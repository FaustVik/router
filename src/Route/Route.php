<?php

declare(strict_types=1);

namespace FaustVik\Router\Route;

use FaustVik\Router\interfaces\Routes\RouteClassInterface;
use FaustVik\Router\interfaces\Routes\RouteInterface;

final class Route implements RouteClassInterface
{
    private string $route = '';
    private string $class = '';
    private string $action = '';

    /** @var array<int, string> HTTP методы (GET, POST, и т.д.) */
    private array $methods = [];

    private ?string $alias = null;

    /** @var array<int, mixed> Аргументы для передачи в action */
    private array $arg = [];

    /** @var array<int, string|object|callable> Middleware для маршрута */
    private array $middleware = [];

    private ?string $name = null;

    /** @var array<string, string> Constraints для параметров маршрута */
    private array $constraints = [];

    /**
     * Создает новый маршрут
     *
     * @param string $route URI паттерн маршрута (например '/users/{id}')
     * @param string $class Класс контроллера
     * @param string $action Метод контроллера
     * @param array<int, mixed> $arg Аргументы для передачи в action
     * @param array<int, string> $methods HTTP методы (GET, POST, и т.д.)
     * @param string|null $alias Альтернативный путь к маршруту
     * @return RouteInterface
     *
     * @example
     * // С named arguments (рекомендуется)
     * Route::create(
     *     route: '/users/{id}',
     *     class: UserController::class,
     *     action: 'show',
     *     methods: ['GET'],
     *     alias: '/user/{id}'
     * );
     *
     * @example
     * // Простой вариант
     * Route::create('/users', UserController::class, 'index');
     *
     * @example
     * // С middleware и constraints
     * Route::create('/admin/users/{id}', AdminController::class, 'edit', methods: ['GET', 'POST'])
     *     ->middleware([AuthMiddleware::class, AdminMiddleware::class])
     *     ->where('id', '\d+')
     *     ->name('admin.users.edit');
     */
    public static function create(
        string $route,
        string $class,
        string $action,
        array $arg = [],
        array $methods = [],
        ?string $alias = null
    ): RouteInterface {
        $self = new self();
        $self->route = $route;
        $self->class = $class;
        $self->action = $action;
        $self->methods = $methods;
        $self->alias = $alias;
        $self->arg = $arg;

        return $self;
    }

    /**
     * Получает класс контроллера
     *
     * @return string Полное имя класса контроллера
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Получает URI паттерн маршрута
     *
     * @return string URI паттерн (например '/users/{id}')
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * Получает имя метода контроллера
     *
     * @return string Имя метода (action)
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Получает список разрешенных HTTP методов
     *
     * @return array<int, string> Массив HTTP методов (GET, POST, и т.д.)
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Получает альтернативный путь (alias) маршрута
     *
     * @return string|null Alias или null если не установлен
     */
    public function alias(): ?string
    {
        return $this->alias;
    }

    /**
     * Устанавливает alias для маршрута
     *
     * @param string $alias Альтернативный путь к маршруту
     * @return self Возвращает себя для fluent interface
     */
    public function setAlias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Получает аргументы для передачи в action
     *
     * @return array<int, mixed> Массив аргументов
     */
    public function getArg(): array
    {
        return $this->arg;
    }

    /**
     * Получает middleware маршрута
     *
     * @return array<int, string|object|callable> Массив middleware
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Устанавливает middleware для маршрута
     *
     * @param array<int, string|object|callable> $middleware Массив middleware (класс, объект или callable)
     * @return self Возвращает себя для fluent interface
     *
     * @example
     * $route->middleware([AuthMiddleware::class, new LoggingMiddleware()]);
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * Получает имя маршрута
     *
     * @return string|null Имя маршрута или null если не установлено
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Устанавливает имя маршрута
     *
     * @param string $name Имя маршрута для генерации URL
     * @return self Возвращает себя для fluent interface
     *
     * @example
     * $route->name('users.show');
     * // Использование: $router->url('users.show', ['id' => 123]);
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Получает constraints для параметров маршрута
     *
     * @return array<string, string> Ассоциативный массив [параметр => паттерн]
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * Устанавливает constraint (ограничение) для параметра маршрута
     *
     * @param string $param Имя параметра из URI (без фигурных скобок)
     * @param string $pattern Регулярное выражение для валидации
     * @return self Возвращает себя для fluent interface
     *
     * @example
     * $route->where('id', '\d+');        // Только цифры
     * $route->where('slug', '[a-z-]+');  // Буквы и дефисы
     */
    public function where(string $param, string $pattern): self
    {
        $this->constraints[$param] = $pattern;
        return $this;
    }
}
