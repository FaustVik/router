# Tests

Тесты для библиотеки FaustVik Router, написанные с использованием PHPUnit 10.

## Структура тестов

```
tests/
├── Http/
│   ├── CookieTest.php    # Тесты для класса Cookie
│   ├── RequestTest.php   # Тесты для класса Request
│   └── ResponseTest.php  # Тесты для класса Response
└── README.md
```

## Запуск тестов

### Все тесты
```bash
composer test
```

### С отчетом о покрытии кода (HTML)
```bash
composer test-coverage
```

Отчет будет создан в директории `.var/coverage/index.html`

### Напрямую через PHPUnit
```bash
./vendor/bin/phpunit
```

### Конкретный тест
```bash
./vendor/bin/phpunit tests/Http/CookieTest.php
```

### С фильтром по методу
```bash
./vendor/bin/phpunit --filter testConstructorSetsDefaultValues
```

## Покрытие кода

Текущее покрытие тестами:
- **Http/Cookie.php** - 100%
- **Http/Request.php** - высокое покрытие основных методов
- **Http/Response.php** - 100%

## Написание тестов

### Соглашения

1. **Именование классов**: `{ClassName}Test.php`
2. **Именование методов**: `test{MethodName}` или `test{Feature}`
3. **Структура теста**: Arrange → Act → Assert
4. **Классы тестов**: помечаем как `final class`

### Пример теста

```php
<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Http;

use PHPUnit\Framework\TestCase;

final class ExampleTest extends TestCase
{
    public function testExampleMethod(): void
    {
        // Arrange - подготовка данных
        $value = 'test';
        
        // Act - выполнение действия
        $result = strtoupper($value);
        
        // Assert - проверка результата
        $this->assertSame('TEST', $result);
    }
}
```

### Что тестируем

1. **Функциональность** - все публичные методы работают правильно
2. **Граничные случаи** - пустые значения, null, крайние значения
3. **Иммутабельность** - методы с `with*` не изменяют оригинальный объект
4. **Fluent Interface** - цепочки методов работают корректно
5. **Значения по умолчанию** - конструкторы устанавливают правильные дефолты

## Требования

- PHP >= 8.1
- PHPUnit 10.5+

## CI/CD

Тесты автоматически запускаются при каждом коммите/пуш через GitHub Actions (если настроено).

