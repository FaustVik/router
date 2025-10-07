# Реализация DI в MiddlewareStack

**Дата:** 7 октября 2025  
**Версия:** v2.0-alpha  
**Issue:** #4 из ROUTER_ISSUES_2025.md

---

## 🎯 Проблема

Middleware с зависимостями в конструкторе не работали:

```php
class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CacheInterface $cache,  // ❌ Откуда взять?
        private int $maxAttempts = 60
    ) {}
}

$route->middleware([RateLimitMiddleware::class]); // ❌ ArgumentCountError!
```

**Причина:** `MiddlewareStack` создавал middleware напрямую через `new $className()` без использования DI контейнера.

---

## ✅ Решение

Реализован **опциональный** DI контейнер с **graceful degradation**:

### Принципы решения:

1. **Простота для новичков** - middleware без зависимостей работают без настройки DI
2. **Мощность для профи** - middleware с зависимостями работают через DI контейнер
3. **Обратная совместимость** - контейнер опциональный, старый код продолжает работать
4. **Понятные ошибки** - если middleware требует зависимости, ошибка объясняет что делать

---

## 📝 Изменения в коде

### 1. MiddlewareStack.php

#### Конструктор с опциональным контейнером:
```php
public function __construct(
    callable $finalHandler, 
    ?RouterContainerInterface $container = null  // ✅ Опциональный параметр
)
```

#### Новый метод resolveMiddleware():
```php
private function resolveMiddleware(string $className): MiddlewareInterface
{
    // 1. Проверяем существование класса
    if (!class_exists($className)) {
        throw new \RuntimeException("Middleware class not found: {$className}");
    }

    // 2. Пытаемся разрешить через DI контейнер
    if ($this->container !== null && $this->container->canResolve($className)) {
        return $this->container->resolve($className);
    }

    // 3. Fallback: создаем напрямую (для middleware без зависимостей)
    try {
        return new $className();
    } catch (\ArgumentCountError $e) {
        throw new \RuntimeException(
            "Middleware {$className} requires constructor dependencies, " .
            "but no DI container is available. Either register it in the " .
            "DI container or pass an instance instead of class name."
        );
    }
}
```

### 2. Router.php

Передача контейнера в MiddlewareStack:
```php
// Было:
$middlewareStack = new MiddlewareStack($finalHandler);

// Стало:
$middlewareStack = new MiddlewareStack($finalHandler, $this->container);
```

---

## 🚀 Использование

### Для новичков (без DI)

```php
<?php
use FaustVik\Router\Route\Route;

// Простой middleware без зависимостей
class SimpleAuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!isset($_SESSION['user'])) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}

// ✅ Работает без DI контейнера
$route = Route::create('/api/users', UserController::class, 'index')
    ->middleware([SimpleAuthMiddleware::class]);
```

### Для опытных разработчиков (с DI)

```php
<?php
use FaustVik\Router\Router\Router;

// Middleware с зависимостями
class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CacheInterface $cache,
        private int $maxAttempts = 60
    ) {}
    
    public function handle(Request $request, callable $next): Response
    {
        $key = 'rate_limit:' . $request->getClientIp();
        $attempts = (int) $this->cache->get($key);
        
        if ($attempts >= $this->maxAttempts) {
            return Response::json(['error' => 'Too many requests'], 429);
        }
        
        $this->cache->set($key, $attempts + 1, 60);
        return $next($request);
    }
}

// Настройка DI
$router = new Router();
$container = $router->getContainer();

// Регистрируем зависимости
$container->singleton(CacheInterface::class, new FileCache());

$container->bind(RateLimitMiddleware::class, function($c) {
    return new RateLimitMiddleware(
        cache: $c->resolve(CacheInterface::class),
        maxAttempts: 100
    );
});

// ✅ Работает через DI!
$route = Route::create('/api/limited', ApiController::class, 'index')
    ->middleware([RateLimitMiddleware::class]);
```

---

## 📋 Преимущества

### ✅ Для новичков:
- Не нужно знать что такое DI
- Простые middleware работают "из коробки"
- Нет необходимости настраивать контейнер
- Нет breaking changes

### ✅ Для опытных разработчиков:
- Полноценный DI для сложных middleware
- Автоматическое разрешение зависимостей
- Возможность использовать singleton и другие паттерны
- Переиспользование зависимостей

### ✅ Для библиотеки:
- Сохранена простота для базового использования
- Добавлена мощность для продвинутого использования
- Обратная совместимость
- Соответствие философии проекта

---

## 🧪 Тестирование

См. пример: `examples/middleware-di-example.php`

Пример демонстрирует:
1. ✅ Простой middleware без зависимостей (без DI)
2. ✅ Rate Limiting middleware с зависимостями (через DI)
3. ✅ Auth middleware с зависимостями (через DI)
4. ✅ Комбинация нескольких middleware
5. ✅ Понятные сообщения об ошибках

Запуск:
```bash
cd examples
php middleware-di-example.php
```

---

## 📊 Метрики

| Метрика | До | После |
|---------|-----|-------|
| Middleware с зависимостями | ❌ Не работали | ✅ Работают |
| Простые middleware | ✅ Работали | ✅ Работают |
| Обратная совместимость | - | ✅ Сохранена |
| Понятность ошибок | 🟡 Средняя | ✅ Высокая |

---

## 🔗 Связанные документы

- `docs/ROUTER_ISSUES_2025.md` - Issue #4 (отмечен как решенный)
- `examples/middleware-di-example.php` - Демонстрация работы
- `examples/README.md` - Добавлен новый пример
- `src/Middleware/MiddlewareStack.php` - Основные изменения
- `src/Router/Router.php` - Передача контейнера

---

## 💡 Будущие улучшения

Возможные улучшения (не критичные):

1. **Автоматический autowiring** - автоматическое разрешение зависимостей без явной регистрации
2. **Middleware priorities** - возможность указать приоритет выполнения middleware
3. **Conditional middleware** - middleware которые выполняются только при определенных условиях
4. **Middleware groups** - именованные наборы middleware для переиспользования

---

**Статус:** ✅ Завершено  
**Автор:** Claude (Sonnet 4.5)  
**Reviewer:** Victor

