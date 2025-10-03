# Future Plans - Планы на будущее

> Идеи и фичи для будущих версий роутера

**Дата создания:** 3 октября 2025  
**Статус:** v2.0-alpha

---

## 🎯 Принципы развития

1. **Простота прежде всего** - QuickRouter должен оставаться простым
2. **Обратная совместимость** - не ломаем существующий код
3. **Опциональность** - сложные фичи должны быть опциональными
4. **Convention over Configuration** - умные дефолты

---

## 🔮 Планы на будущее

### 1. Error Handler (Priority: HIGH) 🔴

**Проблема:**  
Сейчас роутер просто пробрасывает исключения наверх. Пользователь должен сам ловить и обрабатывать ошибки.

**Решение:**  
Добавить опциональный ErrorHandler для кастомной обработки ошибок.

**API:**
```php
interface ErrorHandlerInterface
{
    public function handle(\Throwable $exception): void;
}

class Router
{
    private ?ErrorHandlerInterface $errorHandler = null;
    
    public function setErrorHandler(ErrorHandlerInterface $handler): self
    {
        $this->errorHandler = $handler;
        return $this;
    }
}
```

**Использование:**
```php
// Вариант 1: Свой класс
class JsonErrorHandler implements ErrorHandlerInterface
{
    public function handle(\Throwable $e): void
    {
        http_response_code($e->getCode() ?: 500);
        echo json_encode([
            'error' => get_class($e),
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

$router->setErrorHandler(new JsonErrorHandler());

// Вариант 2: Анонимный класс
$router->setErrorHandler(new class implements ErrorHandlerInterface {
    public function handle(\Throwable $e): void {
        if ($e instanceof ValidationException) {
            Response::json(['errors' => $e->getErrors()], 400)->send();
            exit;
        }
        
        if ($e instanceof NoMatch) {
            Response::html('<h1>404 Not Found</h1>', 404)->send();
            exit;
        }
        
        throw $e; // Пробрасываем остальные
    }
});
```

**Преимущества:**
- ✅ Централизованная обработка ошибок
- ✅ Не нужно оборачивать `$router->run()` в try-catch
- ✅ Опционально - по умолчанию просто пробрасывает
- ✅ Легко переключаться между JSON/HTML/Custom

**Недостатки:**
- ⚠️ Еще один интерфейс для изучения
- ⚠️ Может быть избыточно для простых проектов

**Реализация:**
- [ ] Создать `ErrorHandlerInterface`
- [ ] Добавить `setErrorHandler()` в Router
- [ ] Добавить встроенные handlers: `JsonErrorHandler`, `HtmlErrorHandler`
- [ ] Обновить документацию
- [ ] Добавить примеры в `examples/`

---

### 2. Events System (Priority: MEDIUM) 🟡

**Идея:**  
Система событий для хуков в жизненном цикле роутера.

**API:**
```php
$router->on('before.route', function($uri) {
    // Логирование, аналитика
});

$router->on('after.route', function($response) {
    // Модификация ответа
});

$router->on('error', function($exception) {
    // Обработка ошибок через events
});
```

**Приоритет:** Средний  
**Статус:** Идея

---

### 3. Middleware Pipelines (Priority: LOW) 🟢

**Идея:**  
Более гибкая работа с middleware.

**API:**
```php
$router->pipeline('api', [
    AuthMiddleware::class,
    RateLimitMiddleware::class,
    JsonMiddleware::class
]);

$router->get('/api/users', [UserController::class, 'index'])
    ->pipeline('api');
```

**Приоритет:** Низкий  
**Статус:** Идея

---

### 4. Route Caching Improvements (Priority: MEDIUM) 🟡

**Проблема:**  
Текущий кеш работает, но можно оптимизировать.

**Идеи:**
- Кеш скомпилированных маршрутов (PHP файл с массивом)
- Автоматическая инвалидация при изменении роутов
- Opcache friendly кеш

**Приоритет:** Средний  
**Статус:** Идея

---

### 5. Named Routes (Priority: HIGH) 🔴

**Проблема:**  
Сложно генерировать URL для роутов.

**API:**
```php
$router->get('/users/{id}', [UserController::class, 'show'])
    ->name('user.show');

// Генерация URL
$url = $router->route('user.show', ['id' => 123]); // /users/123
```

**Приоритет:** Высокий  
**Статус:** В планах для v2.1

---

### 6. Resource Routes (Priority: MEDIUM) 🟡

**Идея:**  
Автоматическая регистрация CRUD маршрутов.

**API:**
```php
$router->resource('posts', PostController::class);

// Создаст:
// GET    /posts           -> index
// GET    /posts/{id}      -> show
// POST   /posts           -> store
// PUT    /posts/{id}      -> update
// DELETE /posts/{id}      -> destroy
```

**Приоритет:** Средний  
**Статус:** Идея

---

### 7. Auto Type Casting (Priority: HIGH) 🔴

**Проблема:**  
Параметры приходят как строки, нужно вручную кастить.

**Решение:**
```php
class UserController
{
    // Автоматический каст $id в int
    public function show(int $id)
    {
        // $id уже int!
    }
}
```

**Приоритет:** Высокий  
**Статус:** В планах для v2.1

---

### 8. Route Groups with Attributes (Priority: LOW) 🟢

**Идея:**  
PHP 8 атрибуты для роутов.

**API:**
```php
#[Route('/api')]
#[Middleware(AuthMiddleware::class)]
class UserController
{
    #[Get('/users')]
    public function index() {}
    
    #[Get('/users/{id}')]
    public function show(int $id) {}
}
```

**Приоритет:** Низкий  
**Статус:** Идея для v3.0

---

### 9. Performance: Radix Tree (Priority: LOW) 🟢

**Проблема:**  
O(n) поиск маршрутов медленный для больших приложений.

**Решение:**  
Radix Tree для O(1) поиска (как в FastRoute).

**Приоритет:** Низкий (для мелких проектов O(n) достаточно)  
**Статус:** Идея для v3.0

---

### 10. PSR-7 / PSR-15 Support (Priority: MEDIUM) 🟡

**Идея:**  
Полная поддержка PSR стандартов.

**Приоритет:** Средний  
**Статус:** Рассматривается для v2.1

---

## 📝 Как предложить идею?

1. Открыть Issue на GitHub
2. Описать use case
3. Предложить API
4. Обсудить с сообществом

---

## 🗳️ Голосование за фичи

Если вам нужна какая-то фича - поставьте 👍 на соответствующий Issue в GitHub!

---

**Версия:** 1.0  
**Дата:** 3 октября 2025  
**Статус:** Living Document (будет обновляться)

