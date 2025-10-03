# Краткая сводка проблем Router v2.0-alpha

> Краткий обзор основных проблем и быстрые решения

---

## 🚨 TOP-5 Критических проблем

### 1. ⚠️ Отсутствие тестов
- **Проблема:** 0% test coverage, нет PHPUnit
- **Риск:** Баги в production, опасный рефакторинг
- **Быстрое решение:** `composer require --dev phpunit/phpunit`

### 2. 🔐 XSS уязвимость
- **Файл:** `src/Router/Router.php:170`
- **Код:** `echo "Validation Error: " . $e->getMessage();`
- **Быстрое решение:** Использовать `Response::json()` вместо `echo`

### 3. 🔓 PHP Object Injection
- **Файл:** `src/Cache/FileCache.php:49`
- **Код:** `unserialize($content)`
- **Быстрое решение:** Заменить на `json_decode($content, true)`

### 4. 📝 Нет обработки POST/PUT body
- **Проблема:** Request не читает `php://input`, `$_POST`, `$_FILES`
- **Риск:** REST API не работает с JSON/forms
- **Быстрое решение:** Добавить `getBody()`, `input()`, `file()` методы

### 5. 🚫 Несоответствие PSR стандартам
- **Проблема:** Не реализует PSR-7, PSR-15
- **Риск:** Нет совместимости с экосистемой
- **Решение:** Долгосрочный рефакторинг (2-4 недели)

---

## 📊 Статистика

- **Всего проблем:** 31
- **Критических:** 4
- **Высоких:** 11
- **Средних:** 11
- **Низких:** 5

**Время на исправление:** ~14 недель (3.5 месяца)

---

## 🎯 Что делать в первую очередь?

### Сегодня (30 минут):
```bash
# 1. Добавить .gitignore
echo "/vendor/
/cache/
/custom_cache/
*.log
*.cache
.env" > .gitignore

# 2. Установить PHPUnit
composer require --dev phpunit/phpunit

# 3. Зафиксировать изменения
git add .gitignore composer.json docs/
git commit -m "docs: add technical issues analysis and .gitignore"
```

### Эта неделя (8 часов):

**День 1-2: Безопасность**
```php
// src/Router/Router.php:166-172
// БЫЛО:
catch (ValidationException $e) {
    http_response_code(400);
    echo "Validation Error: " . $e->getMessage(); // XSS!
    exit;
}

// СТАЛО:
catch (ValidationException $e) {
    $response = Response::json([
        'error' => 'Validation failed',
        'details' => $e->getErrors()
    ], 400);
    $response->send();
    return;
}
```

```php
// src/Cache/FileCache.php:49
// БЫЛО:
$data = unserialize($content);

// СТАЛО:
$data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
```

**День 3-5: Тесты**
```bash
mkdir -p tests/{Unit,Integration,Feature}

# Создать базовые тесты:
# tests/Unit/Router/MatchingTest.php
# tests/Unit/Validation/ParameterValidatorTest.php
# tests/Integration/RouterTest.php
```

---

## 📋 Чеклист быстрых исправлений

### Безопасность (2 часа)
- [ ] Исправить XSS в Router::validateParameters()
- [ ] Заменить unserialize на json_decode
- [ ] Добавить валидацию $cacheDir в FileCache
- [ ] Добавить htmlspecialchars в Request::getParam()

### Тесты (6 часов)
- [ ] Установить PHPUnit
- [ ] Создать структуру tests/
- [ ] Написать 5 базовых unit тестов
- [ ] Настроить phpunit.xml
- [ ] Добавить в composer.json: "test": "phpunit"

### Функциональность (4 часа)
- [ ] Добавить Request::getBody()
- [ ] Добавить Request::input($key)
- [ ] Добавить Request::file($key)
- [ ] Обработать JSON в createFromGlobals()

### Документация (2 часа)
- [ ] Создать CHANGELOG.md
- [ ] Создать LICENSE
- [ ] Добавить badge в README.md
- [ ] Обновить README с новыми возможностями

### Организация (1 час)
- [ ] Добавить .gitignore
- [ ] Удалить *.log из git
- [ ] Удалить cache/ из git
- [ ] Создать .github/workflows/tests.yml

---

## 🔧 Готовые snippets для копирования

### .gitignore
```gitignore
/vendor/
/node_modules/
/cache/
/custom_cache/
*.cache
*.log
/logs/
/.idea/
/.vscode/
/.cursor/
.DS_Store
.env
.env.local
/build/
/.phpunit.cache/
/coverage/
```

### phpunit.xml
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php"
         colors="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory>src</directory>
        </include>
    </coverage>
</phpunit>
```

### GitHub Actions: .github/workflows/tests.yml
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: [8.1, 8.2, 8.3]
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          
      - name: Install dependencies
        run: composer install --prefer-dist
        
      - name: Run tests
        run: composer test
        
      - name: Run PHPStan
        run: composer phpstan
        
      - name: Check code style
        run: composer cs-check
```

### Базовый тест

```php
<?php
// tests/Unit/Router/MatchingTest.php

namespace Tests\Unit\Router;

use FaustVik\Router\Route\Route;use FaustVik\Router\Route\RoutesCollection;use FaustVik\Router\Router\Components\matching\Matching;use PHPUnit\Framework\TestCase;

class MatchingTest extends TestCase
{
    public function testMatchExactRoute(): void
    {
        $matching = new Matching();
        $collection = new RoutesCollection();
        
        $route = Route::create('/', TestController::class, 'index', [], ['GET']);
        $collection->set($route);
        
        $result = $matching->match('/', $collection);
        
        $this->assertSame($route, $result->getRoute());
        $this->assertEmpty($result->getParameters());
    }
    
    public function testMatchRouteWithParameter(): void
    {
        $matching = new Matching();
        $collection = new RoutesCollection();
        
        $route = Route::create('/users/{id}', TestController::class, 'show', [], ['GET']);
        $collection->set($route);
        
        $result = $matching->match('/users/123', $collection);
        
        $this->assertSame($route, $result->getRoute());
        $this->assertEquals(['id' => '123'], $result->getParameters());
    }
}
```

### Request с POST body
```php
// src/Http/Request.php - добавить после существующих свойств

private array $body = [];
private array $files = [];

public static function createFromGlobals(): self
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $query = $_GET;
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $server = $_SERVER;
    
    // Обработка POST/PUT body
    $body = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (str_contains($contentType, 'application/json')) {
        $rawBody = file_get_contents('php://input');
        $body = json_decode($rawBody, true) ?? [];
    } elseif (in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $body = $_POST;
    }
    
    $request = new self($method, $uri, [], $query, $headers, $server);
    $request->body = $body;
    $request->files = $_FILES;
    
    return $request;
}

public function getBody(): array
{
    return $this->body;
}

public function input(string $key, mixed $default = null): mixed
{
    return $this->body[$key] ?? $default;
}

public function file(string $key): ?array
{
    return $this->files[$key] ?? null;
}

public function hasFile(string $key): bool
{
    return isset($this->files[$key]) 
        && $this->files[$key]['error'] === UPLOAD_ERR_OK;
}
```

---

## 📈 Roadmap к stable release

```
v2.0.0-alpha (current)
    ↓
[Sprint 1] Security & Tests (2-3 weeks)
    ↓
v2.0.0-alpha.2
    ↓
[Sprint 2] Architecture (3-4 weeks)
    ↓
v2.0.0-beta
    ↓
[Sprint 3] Features (4-5 weeks)
    ↓
v2.0.0-rc1
    ↓
[Sprint 4] Polish & Beta Testing (2 weeks)
    ↓
v2.0.0 STABLE 🎉
```

**ETA до stable:** ~3.5 месяца (14 недель)

---

## 💡 Полезные ссылки

- 📄 [Полный анализ проблем](TECHNICAL_ISSUES.md) - детальное описание всех 31 проблем
- 🧪 [PHPUnit Documentation](https://phpunit.de/)
- 🔒 [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- 📦 [PSR Standards](https://www.php-fig.org/psr/)
- 🚀 [Semantic Versioning](https://semver.org/)

---

**Создано:** 3 октября 2025  
**Для полного анализа смотрите:** [TECHNICAL_ISSUES.md](TECHNICAL_ISSUES.md)

