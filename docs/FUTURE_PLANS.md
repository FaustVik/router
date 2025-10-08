# Future Plans - Планы на будущее

> Идеи и фичи для будущих версий роутера

**Дата обновления:** 8 октября 2025  
**Статус:** v2.0-alpha

---

## 🎯 Принципы развития

1. **Простота прежде всего** - QuickRouter должен оставаться простым
2. **Обратная совместимость** - не ломаем существующий код
3. **Опциональность** - сложные фичи должны быть опциональными
4. **Convention over Configuration** - умные дефолты

---

## ✅ Что уже реализовано

- ✅ **Named Routes** - маршруты с именами
- ✅ **URL Generation** - генерация URL по имени
- ✅ **Query параметры** - `url($name, $params, $query, $fragment)`
- ✅ **Якоря/фрагменты** - поддержка `#section`
- ✅ **Глобальные middleware** - применяются ко всем маршрутам
- ✅ **DI в middleware** - автоматическое разрешение зависимостей
- ✅ **Оптимизация parse()** - использование `parse_url()`

---

## 🔮 Планы на будущее

### 1. Resource Routes (Priority: MEDIUM) 🟡

**Идея:**  
Автоматическое создание REST маршрутов для ресурса одной строкой.

**API:**
```php
// Вместо 7 маршрутов
$routes->resource('/posts', PostController::class);

// Создаст автоматически:
// GET    /posts           -> index()
// GET    /posts/create    -> create()
// POST   /posts           -> store()
// GET    /posts/{id}      -> show()
// GET    /posts/{id}/edit -> edit()
// PUT    /posts/{id}      -> update()
// DELETE /posts/{id}      -> destroy()

// С опциями
$routes->resource('/posts', PostController::class, [
    'only' => ['index', 'show'], // Только эти методы
    'except' => ['destroy'],      // Кроме этих
    'names' => [                  // Кастомные имена
        'index' => 'posts.all',
    ]
]);
```

**Преимущества:**
- Быстрое создание CRUD API
- Стандартизация именования
- Меньше кода

---

### 2. Auto Type Casting (Priority: LOW) 🟢

**Идея:**  
Автоматическое приведение типов параметров URL.

**API:**
```php
// Сейчас
Route::create('/users/{id}', function($id) {
    $id = (int)$id; // Ручное приведение
});

// С auto-casting
Route::create('/users/{id:int}', function(int $id) {
    // $id уже int!
});

Route::create('/posts/{slug:string}', function(string $slug) {});
Route::create('/archive/{date:date}', function(DateTime $date) {});

// Кастомные типы
$router->registerType('uuid', function($value) {
    if (!Uuid::isValid($value)) {
        throw new InvalidParameterException();
    }
    return Uuid::fromString($value);
});

Route::create('/api/{id:uuid}', function(UuidInterface $id) {});
```

**Реализация:**
- Парсинг типа из паттерна `{param:type}`
- Приведение типа перед передачей в controller
- Выброс исключения при ошибке приведения

---

### 3. Route Model Binding (Priority: LOW) 🟢

**Идея:**  
Автоматическая загрузка моделей из базы данных по ID.

**API:**
```php
// Сейчас
Route::create('/users/{id}', function($id) {
    $user = User::find($id);
    if (!$user) {
        throw new NotFoundException();
    }
    // ...
});

// С model binding
Route::create('/users/{user:User}', function(User $user) {
    // $user уже загружен из БД!
});

// Кастомная логика загрузки
$router->bindModel(User::class, function($value) {
    return User::where('slug', $value)->firstOrFail();
});

Route::create('/users/{user:User}', function(User $user) {
    // Загружается по slug вместо id
});
```

**Требует:**
- Интеграция с ORM (Eloquent, Doctrine, или кастомный)
- Настройка через конфиг или bind

---

### 4. Events System (Priority: LOW) 🟢

**Идея:**  
Система событий для хуков в жизненном цикле роутера.

**API:**
```php
// События
$router->on('router.before', function($event) {
    // До маршрутизации
});

$router->on('router.matched', function($event) {
    // После нахождения маршрута
    $route = $event->getRoute();
});

$router->on('router.after', function($event) {
    // После выполнения
    $response = $event->getResponse();
});

$router->on('router.exception', function($event) {
    // При исключении
    $exception = $event->getException();
});

// Практическое использование
$router->on('router.matched', function($event) {
    $route = $event->getRoute();
    
    // Автоматическое логирование
    if ($route->hasTag('log')) {
        logger()->info("Route matched", ['route' => $route->getName()]);
    }
});
```

---

### 5. Rate Limiter с Redis (Priority: MEDIUM) 🟡

**Идея:**  
Расширить RateLimitMiddleware поддержкой Redis для распределенных систем.

**API:**
```php
// С Redis
$redis = new Redis();
$redis->connect('localhost', 6379);

$router->addGlobalMiddleware(
    new RateLimitMiddleware(
        cache: new RedisCache($redis),
        maxAttempts: 100,
        decayMinutes: 1
    )
);

// С разными лимитами для разных маршрутов
$route->middleware([
    new RateLimitMiddleware(
        cache: $cache,
        maxAttempts: 10,  // Строгий лимит для этого маршрута
        decayMinutes: 1
    )
]);
```

---

### 6. Request Validation (Priority: LOW) 🟢

**Идея:**  
Встроенная валидация данных запроса.

**API:**
```php
Route::create('/users', [UserController::class, 'store'])
    ->validate([
        'name' => 'required|string|min:3',
        'email' => 'required|email|unique:users',
        'age' => 'integer|min:18'
    ]);

// В контроллере
public function store(Request $request)
{
    $validated = $request->validated();
    // Данные уже валидны!
}

// Кастомные правила
$router->addValidationRule('phone', function($value) {
    return preg_match('/^\+?[0-9]{10,15}$/', $value);
});
```

**Или проще - интеграция с существующими:**
```php
// Интеграция с Respect\Validation
Route::create('/users', [UserController::class, 'store'])
    ->validate(new UserCreateValidator());
```

---

### 7. API Versioning (Priority: LOW) 🟢

**Идея:**  
Удобная система версионирования API.

**API:**
```php
// Вариант 1: Через префикс
$router->version('v1', function($r) {
    $r->get('/users', [V1\UserController::class, 'index']);
});

$router->version('v2', function($r) {
    $r->get('/users', [V2\UserController::class, 'index']);
});

// Вариант 2: Через header
$router->versionByHeader('X-API-Version', [
    '1' => function($r) {
        $r->get('/users', [V1\UserController::class, 'index']);
    },
    '2' => function($r) {
        $r->get('/users', [V2\UserController::class, 'index']);
    }
]);

// Вариант 3: Через subdomain
$router->versionBySubdomain([
    'api.v1' => function($r) { /* ... */ },
    'api.v2' => function($r) { /* ... */ }
]);
```

---

### 8. OpenAPI/Swagger документация (Priority: LOW) 🟢

**Идея:**  
Автоматическая генерация OpenAPI спецификации из маршрутов.

**API:**
```php
Route::create('/users/{id}', [UserController::class, 'show'])
    ->name('users.show')
    ->describe([
        'summary' => 'Get user by ID',
        'parameters' => [
            'id' => ['type' => 'integer', 'description' => 'User ID']
        ],
        'responses' => [
            200 => ['description' => 'User found', 'schema' => UserSchema::class],
            404 => ['description' => 'User not found']
        ]
    ]);

// Генерация документации
$openapi = $router->generateOpenAPI([
    'title' => 'My API',
    'version' => '1.0.0'
]);

file_put_contents('openapi.json', json_encode($openapi));
```

---

## 📋 Приоритеты

### Высокий приоритет 🔴
- (Нет в планах - фокус на стабильности)

### Средний приоритет 🟡
1. **Resource Routes** - популярная фича, экономит время
2. **Rate Limiter с Redis** - нужно для production

### Низкий приоритет 🟢
1. **Auto Type Casting** - удобно, но не критично
2. **Route Model Binding** - требует интеграции с ORM
3. **Events System** - интересно, но можно обойтись middleware
4. **Request Validation** - есть готовые библиотеки
5. **API Versioning** - можно реализовать через middleware
6. **OpenAPI документация** - nice-to-have

---

## 💭 Что точно НЕ будем делать

### ❌ Полноценный ORM
**Почему:** Это не задача роутера. Используйте Eloquent, Doctrine, или др.

### ❌ Template Engine
**Почему:** Используйте Twig, Blade, или нативный PHP.

### ❌ Авторизация/Аутентификация
**Почему:** Слишком специфично для каждого проекта. Реализуйте через middleware.

### ❌ Session Management
**Почему:** PHP уже имеет встроенные сессии, не нужно дублировать.

### ❌ Database Migrations
**Почему:** Это задача ORM или отдельных инструментов (Phinx, Doctrine Migrations).

---

## 🎯 Фокус проекта

Router должен оставаться **библиотекой для маршрутизации**, а не превращаться в фреймворк.

**Границы ответственности:**
- ✅ Маршрутизация HTTP запросов
- ✅ Middleware система
- ✅ Dependency Injection
- ✅ Request/Response объекты
- ✅ Базовая безопасность
- ❌ База данных
- ❌ Шаблоны
- ❌ Авторизация (кроме базовых middleware примеров)

---

**Последнее обновление:** 8 октября 2025  
**Вклад приветствуется!** Если у вас есть идеи - создайте GitHub Issue.
