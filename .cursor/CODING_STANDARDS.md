# Стандарты кодирования

Этот документ описывает стандарты кодирования для проекта FaustVik Router.

## Инструменты

### PHP CodeSniffer
Проект использует PHP CodeSniffer для проверки соответствия кода стандартам.

**Проверка кода:**
```bash
composer run cs-check
# или
vendor/bin/phpcs
```

**Автоматическое исправление:**
```bash
composer run cs-fix
# или
vendor/bin/phpcbf
```

### Основные стандарты

- **PSR-12** - основной стандарт кодирования
- **PHPStan Level 5** - статический анализ кода
- **EditorConfig** - настройки редактора

## Основные правила

### 1. Отступы и форматирование
- Используйте 4 пробела для отступов
- Максимальная длина строки: 120 символов
- Unix-style переносы строк (LF)
- Файлы должны заканчиваться пустой строкой

### 2. Комментарии и документация

#### PHPDoc в контексте строгой типизации

В PHP 8.1+ со строгой типизацией PHPDoc **НЕ нужен** для:

❌ **Простых свойств с явной типизацией:**
```php
// НЕ нужен PHPDoc
private ?RouterContainerInterface $container = null;
private string $method = '';
private array $params = [];
```

❌ **Простых геттеров/сеттеров:**
```php
// НЕ нужен PHPDoc
public function setConfig(ConfigInterface $config): void
{
    $this->config = $config;
}

public function getConfig(): ConfigInterface
{
    return $this->config;
}
```

❌ **Конструкторов без сложной логики:**
```php
// НЕ нужен PHPDoc
public function __construct(string $method, string $uri, array $params = [])
{
    $this->method = $method;
    $this->uri = $uri;
    $this->params = $params;
}
```

#### PHPDoc действительно полезен для:

✅ **Массивов с типизированными элементами:**
```php
/** @var RouteInterface[] */
private array $routes = [];

/** @var MiddlewareInterface[] */
private array $middleware = [];

/**
 * @return RouteInterface[]
 */
public function getRoutes(): array
{
    return $this->routes;
}
```

✅ **Методов с исключениями:**
```php
/**
 * @throws ValidationException Если параметры не прошли валидацию
 * @throws NoMatch Если маршрут не найден
 */
public function run(): void
{
    // ...
}
```

✅ **Сложных типов и mixed:**
```php
/**
 * @param mixed $value
 * @return mixed
 */
public function get(string $key): mixed
{
    // ...
}
```

✅ **Сложного поведения методов:**
```php
/**
 * Парсит URI запроса
 *
 * Разделяет URI на путь и параметры query string.
 * Например: "/users/123?name=John&age=30" ->
 * - $this->uri = "/users/123"
 * - $this->params = ["name" => "John", "age" => "30"]
 */
protected function parse(): void
{
    // ...
}
```

✅ **Параметров с массивами специфических типов:**
```php
/**
 * @param ParameterValidationRule[] $rules
 */
public function validate(array $parameters, array $rules): void
{
    // ...
}
```

### 3. Именование

- **Классы**: PascalCase (`RouterInterface`, `ConfigInterface`)
- **Методы**: camelCase (`setConfig`, `getRoutes`)
- **Переменные**: camelCase (`$routeCollection`, `$parameterValidator`)
- **Константы**: UPPER_CASE (`MAX_ROUTE_LENGTH`)

### 4. Named Arguments (PHP 8.0+)

**Используйте named arguments для улучшения читаемости кода** в следующих случаях:

✅ **При создании объектов с несколькими параметрами:**
```php
// ✅ Хорошо - понятно что включено
$app = new QuickRouter(cache: true, di: false);

// ❌ Плохо - непонятно что означают булевы значения
$app = new QuickRouter(true, false);
```

✅ **При вызове статических фабричных методов:**
```php
// ✅ Хорошо
$route = Route::create(
    route: '/users',
    class: UserController::class,
    action: 'index',
    arg: [],
    methods: ['GET']
);

// ❌ Плохо - порядок параметров неочевиден
$route = Route::create('/users', UserController::class, 'index', [], ['GET']);
```

✅ **В приватных/внутренних методах для self-documenting кода:**
```php
private function addRoute(string|array $methods, string $uri, callable|array $handler): RouteInterface
{
    return $this->createRoute(
        methods: $methods,
        uri: $uri,
        handler: $handler
    );
}
```

❌ **НЕ используйте в простых очевидных случаях:**
```php
// ❌ Избыточно
$app->get(uri: '/', handler: fn() => "Hello!");

// ✅ Достаточно просто
$app->get('/', fn() => "Hello!");
```

**Принцип:** Named arguments улучшают читаемость, когда параметры не очевидны из контекста.

### 5. Структура файлов

Каждый PHP файл должен начинаться со следующей структуры (согласно PSR-12):

```php
<?php

declare(strict_types=1);

namespace FaustVik\Router\Example;
```

**Важно:**
- После открывающего тега `<?php` обязательна **пустая строка**
- После `declare(strict_types=1);` обязательна **пустая строка**
- Затем следует объявление `namespace`

### 6. Структура классов

```php
<?php

declare(strict_types=1);

namespace FaustVik\Router\Example;

use FaustVik\Router\interfaces\ExampleInterface;

final class Example implements ExampleInterface
{
    // Константы
    private const MAX_ITEMS = 100;
    
    // Свойства (приватные, затем защищенные, затем публичные)
    private array $items = [];
    
    // Конструктор
    public function __construct()
    {
        // ...
    }
    
    // Публичные методы
    public function process(): void
    {
        // ...
    }
    
    // Защищенные методы
    protected function validate(): bool
    {
        // ...
    }
    
    // Приватные методы
    private function helper(): void
    {
        // ...
    }
}
```

### 6. Типизация

- Всегда используйте `declare(strict_types=1)`
- Указывайте типы для всех параметров и возвращаемых значений
- Используйте nullable типы (`?string`) вместо `string|null`
- Для массивов используйте `array` в сигнатуре, `Type[]` в PHPDoc

### 7. Исключения

- Создавайте специфические исключения для разных типов ошибок
- Наследуйте от базового `Exception` или специфических исключений
- Используйте `@throws` в PHPDoc для документирования исключений

### 8. Примеры

Файлы в папке `examples/` имеют более мягкие требования:
- Не требуется `declare(strict_types=1)`
- Разрешены `echo`/`print` для демонстрации
- Не требуются пространства имен для простых примеров

## Автоматизация

### GitHub Actions
Проект настроен для автоматической проверки кода через GitHub Actions:
- Проверка синтаксиса PHP
- Запуск PHP CodeSniffer
- Статический анализ PHPStan
- Тестирование примеров

### Команды composer

```bash
# Проверка стандартов кодирования
composer run cs-check

# Автоматическое исправление
composer run cs-fix

# Статический анализ
composer run phpstan

# Создание baseline для PHPStan
composer run phpstan-baseline
```

## Рекомендации

1. **Настройте IDE** для поддержки EditorConfig
2. **Используйте PHPStan** для статического анализа
3. **Запускайте cs-fix** перед коммитом
4. **Пишите PHPDoc** только там, где это действительно нужно
5. **Тестируйте примеры** после изменений в коде

## Конфигурация

Основные файлы конфигурации:
- `phpcs.xml` - настройки PHP CodeSniffer
- `phpstan.neon` - настройки PHPStan
- `.editorconfig` - настройки редактора
- `.github/workflows/code-quality.yml` - CI/CD 