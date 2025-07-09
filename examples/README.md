# Примеры использования FaustVik Router

## Обзор примеров

### Для начинающих
- **`basic-example.php`** - Введение в роутер, базовые маршруты, контроллеры, URL параметры
- **`simple-groups-example.php`** - Простая демонстрация группировки маршрутов

### Для среднего уровня  
- **`url-parameters-example.php`** - Продвинутая работа с URL параметрами, вложенные параметры
- **`response-types-example.php`** - Различные типы ответов: JSON, HTML, редиректы, заголовки
- **`groups-example.php`** - Комплексная группировка роутов с middleware и вложенностью

### Для продвинутых
- **`rest-api-example.php`** - Полноценный REST API с CRUD операциями
- **`error-handling-example.php`** - Обработка ошибок, исключения, HTTP коды

## Новые возможности v.2.0-alpha

### 🎯 Группировка роутов

Группировка позволяет объединять маршруты с общими свойствами:

#### Простая группировка с префиксом
```php
$routes->prefix('/api')->group(function($group) {
    $group->get('/users', UserController::class, 'index');     // /api/users
    $group->post('/users', UserController::class, 'create');   // /api/users
    $group->get('/users/{id}', UserController::class, 'show'); // /api/users/{id}
});
```

#### Группировка с middleware
```php
$routes->middleware([AuthMiddleware::class])
    ->prefix('/admin')
    ->group(function($group) {
        $group->get('/dashboard', AdminController::class, 'dashboard');
        $group->get('/users', AdminController::class, 'users');
    });
```

#### Вложенная группировка
```php
$routes->prefix('/api')->group(function($api) {
    $api->prefix('/v1')->group(function($v1) {
        $v1->get('/users', UserController::class, 'index');  // /api/v1/users
    });
});

$routes->prefix('/api')->group(function($api) {
    $api->prefix('/v2')->group(function($v2) {
        $v2->get('/users', UserController::class, 'index');  // /api/v2/users
    });
});
```

#### Анонимные функции в группах
```php
$routes->prefix('/blog')->group(function($group) {
    $group->getFunc('/rss', function(): Response {
        return Response::create('RSS Feed')->withHeader('Content-Type', 'application/xml');
    });
});
```

#### Методы группировки

**HTTP методы для классов:**
- `get(route, class, action)` - GET запросы
- `post(route, class, action)` - POST запросы  
- `put(route, class, action)` - PUT запросы
- `delete(route, class, action)` - DELETE запросы
- `patch(route, class, action)` - PATCH запросы
- `any(route, class, action)` - Любые HTTP методы
- `match([methods], route, class, action)` - Указанные методы

**HTTP методы для анонимных функций:**
- `getFunc(route, function)` - GET с функцией
- `postFunc(route, function)` - POST с функцией
- `putFunc(route, function)` - PUT с функцией
- `deleteFunc(route, function)` - DELETE с функцией
- `anyFunc(route, function)` - Любые методы с функцией

**Управление группами:**
- `prefix(string)` - Добавить префикс к маршрутам
- `middleware(array)` - Применить middleware к группе
- `group(callable)` - Создать вложенную группу

## Быстрый старт

### 1. Простейший пример
```bash
REQUEST_METHOD="GET" REQUEST_URI="/" php basic-example.php
```

### 2. Тестирование групп
```bash
REQUEST_METHOD="GET" REQUEST_URI="/api/v1/users" php simple-groups-example.php
```

### 3. REST API
```bash
REQUEST_METHOD="GET" REQUEST_URI="/api/users" php rest-api-example.php
```

## Концепции

### 🔄 URL параметры
Маршруты поддерживают параметры в фигурных скобках: `/users/{id}`, `/posts/{postId}/comments/{commentId}`

### 🛡️ Middleware
Middleware обрабатывает запросы до/после контроллеров: аутентификация, логирование, CORS

### 📊 Request/Response
- `Request` объект содержит данные запроса, параметры маршрута, заголовки
- `Response` объект позволяет создавать JSON, HTML, редиректы с заголовками

### ⚡ Производительность
- PHPStan Level 5 - строгая типизация
- Автоматическая инъекция зависимостей
- Совместимость с CLI и веб-сервером

## Тестирование

Все примеры можно тестировать через CLI:

```bash
cd examples/

# Базовый пример
REQUEST_METHOD="GET" REQUEST_URI="/" php basic-example.php

# Группировка роутов
REQUEST_METHOD="GET" REQUEST_URI="/api/v2/users/123" php simple-groups-example.php

# URL параметры
REQUEST_METHOD="GET" REQUEST_URI="/users/123/posts/456/comments/789" php url-parameters-example.php

# JSON ответы
REQUEST_METHOD="GET" REQUEST_URI="/api/users" php response-types-example.php

# REST API
REQUEST_METHOD="POST" REQUEST_URI="/api/users" php rest-api-example.php

# Обработка ошибок
REQUEST_METHOD="GET" REQUEST_URI="/errors/validation" php error-handling-example.php
```

## Структура обучения

1. **Начните с `basic-example.php`** - изучите основы
2. **Перейдите к `simple-groups-example.php`** - освойте группировку  
3. **Изучите `url-parameters-example.php`** - параметры маршрутов
4. **Попробуйте `response-types-example.php`** - разные типы ответов
5. **Создайте API с `rest-api-example.php`** - CRUD операции
6. **Освойте `error-handling-example.php`** - обработку ошибок
7. **Изучите `groups-example.php`** - сложную группировку

Каждый пример содержит подробные комментарии и готов для запуска! 