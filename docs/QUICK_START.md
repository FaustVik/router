# Quick Start Guide - QuickRouter

> Hello World за 5 минут! 🚀

**Дата:** 3 октября 2025  
**Версия:** v2.0-alpha

---

## 🎯 Для кого это?

**QuickRouter** - это упрощенный интерфейс роутера для:
- 👶 Новичков в PHP
- ⚡ Быстрого прототипирования
- 🏠 Мелких проектов (лендинги, простые сайты)
- 📚 Учебных проектов

Если вам нужен enterprise-роутер с DI, кешированием и сложной архитектурой - используйте полный `Router`.

---

## 📦 Установка

```bash
composer require faustvik/router
```

---

## ⚡ Hello World (3 минуты)

### Шаг 1: Создайте файл

```php
<?php
// index.php

require 'vendor/autoload.php';

use FaustVik\Router\Router\QuickRouter;

$app = new QuickRouter();

$app->get('/', function() {
    echo "Hello World!";
});

$app->run();
```

### Шаг 2: Запустите

```bash
php -S localhost:8000
```

### Шаг 3: Откройте браузер

```
http://localhost:8000
```

**Готово!** 🎉

---

## 📖 Основы

### HTTP методы

```php
use FaustVik\Router\Router\QuickRouter;

$app = new QuickRouter();

// GET запрос
$app->get('/users', function() {
    echo "List of users";
});

// POST запрос
$app->post('/users', function() {
    echo "Create user";
});

// PUT запрос
$app->put('/users/{id}', function($id) {
    echo "Update user #$id";
});

// DELETE запрос
$app->delete('/users/{id}', function($id) {
    echo "Delete user #$id";
});

// PATCH запрос
$app->patch('/users/{id}', function($id) {
    echo "Patch user #$id";
});

// Любой HTTP метод
$app->any('/webhook', function() {
    echo "Webhook received";
});

// Конкретные методы
$app->match(['GET', 'POST'], '/form', function() {
    echo "Form handler";
});

$app->run();
```

---

### Параметры в URL

```php
// Один параметр
$app->get('/users/{id}', function($id) {
    echo "User #$id";
});

// Несколько параметров
$app->get('/posts/{category}/{id}', function($category, $id) {
    echo "Category: $category, Post: $id";
});

// Параметры автоматически передаются в функцию
$app->get('/hello/{name}', function($name) {
    echo "Hello, " . htmlspecialchars($name) . "!";
});
```

**Примеры:**
- `/users/42` → `$id = "42"`
- `/posts/tech/123` → `$category = "tech"`, `$id = "123"`
- `/hello/John` → `$name = "John"`

---

### JSON ответы

```php
use FaustVik\Router\Http\Response;

$app->get('/api/users/{id}', function($id) {
    return Response::json([
        'id' => (int) $id,
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);
});

// С кастомным статус кодом
$app->post('/api/users', function() {
    return Response::json([
        'message' => 'User created',
        'id' => 123
    ], 201); // 201 Created
});
```

---

### HTML ответы

```php
use FaustVik\Router\Http\Response;

$app->get('/', function() {
    return Response::html('
        <!DOCTYPE html>
        <html>
        <head>
            <title>My App</title>
        </head>
        <body>
            <h1>Welcome!</h1>
        </body>
        </html>
    ');
});
```

---

### Редиректы

```php
use FaustVik\Router\Http\Response;

$app->get('/old-page', function() {
    return Response::redirect('/new-page');
});

// С кастомным статус кодом
$app->get('/temporary', function() {
    return Response::redirect('/new-location', 307);
});
```

---

### Использование контроллеров

```php
use FaustVik\Router\Router\QuickRouter;

// Создайте контроллер
class UserController
{
    public function index()
    {
        echo "Users list";
    }
    
    public function show($id)
    {
        echo "User #$id";
    }
    
    public function store()
    {
        echo "Create new user";
    }
}

// Создайте роутер и регистрируйте роуты
$app = new QuickRouter();
$app->get('/users', [UserController::class, 'index']);
$app->get('/users/{id}', [UserController::class, 'show']);
$app->post('/users', [UserController::class, 'store']);
```

---

## 🚀 Продвинутые возможности

### Middleware

```php
use FaustVik\Router\Middleware\AuthMiddleware;
use FaustVik\Router\Middleware\LoggingMiddleware;

// Применить middleware к маршруту
$app->get('/admin', [AdminController::class, 'index'])
    ->middleware([
        AuthMiddleware::class,
        LoggingMiddleware::class
    ]);

// Группа с middleware
$app->middleware([AuthMiddleware::class], function($app) {
    $app->get('/admin/users', [AdminController::class, 'users']);
    $app->get('/admin/settings', [AdminController::class, 'settings']);
});
```

---

### Валидация параметров

```php
use FaustVik\Router\Validation\ParameterValidationRule;
use FaustVik\Router\Validation\IntValidator;

$app->get('/users/{id}', [UserController::class, 'show'])
    ->validate([
        ParameterValidationRule::create('id')
            ->required()
            ->addValidator(new IntValidator())
    ]);
```

---

### Доступ к полному API

Когда QuickRouter становится недостаточно, получите доступ к полному Router:

```php
$app = new QuickRouter();

// Используйте простой API
$app->get('/', fn() => "Hello!");

// Когда нужно больше - используйте advanced()
$app->advanced()->enableCache();
$app->advanced()->enableDI();
$app->advanced()->bind(LoggerInterface::class, FileLogger::class);

$app->run();
```

---

## 📁 Структура проекта

Рекомендуемая структура для небольшого проекта:

```
my-project/
├── public/
│   ├── index.php        # Точка входа
│   └── .htaccess        # Для Apache
├── app/
│   ├── Controllers/     # Ваши контроллеры
│   │   ├── HomeController.php
│   │   └── UserController.php
│   └── routes.php       # Определение маршрутов
├── views/               # HTML шаблоны (опционально)
├── .env                 # Конфигурация
└── composer.json
```

**public/index.php:**
```php
<?php
require __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\QuickRouter;

$app = new QuickRouter();

// Подключаем маршруты
require __DIR__ . '/../app/routes.php';

$app->run();
```

**app/routes.php:**
```php
<?php
use App\Controllers\HomeController;
use App\Controllers\UserController;

// Домашняя страница
$app->get('/', [HomeController::class, 'index']);

// Пользователи
$app->get('/users', [UserController::class, 'index']);
$app->get('/users/{id}', [UserController::class, 'show']);
$app->post('/users', [UserController::class, 'store']);
```

---

## 🎓 Примеры

### REST API

```php
$app = new QuickRouter();

// Список
$app->get('/api/users', function() {
    return Response::json([
        ['id' => 1, 'name' => 'John'],
        ['id' => 2, 'name' => 'Jane']
    ]);
});

// Один элемент
$app->get('/api/users/{id}', function($id) {
    return Response::json([
        'id' => (int) $id,
        'name' => 'John Doe'
    ]);
});

// Создать
$app->post('/api/users', function() {
    return Response::json(['message' => 'Created'], 201);
});

// Обновить
$app->put('/api/users/{id}', function($id) {
    return Response::json(['message' => 'Updated']);
});

// Удалить
$app->delete('/api/users/{id}', function($id) {
    return Response::json(['message' => 'Deleted']);
});

$app->run();
```

### Лендинг

```php
$app = new QuickRouter();

$app->get('/', function() {
    return Response::html(file_get_contents('views/home.html'));
});

$app->get('/about', function() {
    return Response::html(file_get_contents('views/about.html'));
});

$app->get('/contact', function() {
    return Response::html(file_get_contents('views/contact.html'));
});

$app->post('/contact', function() {
    // Обработка формы
    return Response::json(['success' => true]);
});

$app->run();
```

---

## 🔧 Конфигурация

### Включить кеширование (для production)

```php
// ✨ Новый стиль (PHP 8.0+ Named Arguments)
$app = new QuickRouter(cache: true);

// Или старый стиль (тоже работает)
$app = new QuickRouter(['cache' => true]);
```

### Включить DI контейнер

```php
// ✨ Новый стиль (рекомендуется)
$app = new QuickRouter(di: true);

// Или старый стиль
$app = new QuickRouter(['di' => true]);
```

### Оба сразу

```php
// ✨ Новый стиль - понятно и читабельно
$app = new QuickRouter(cache: true, di: true);

// Или для условного включения
$isProduction = getenv('APP_ENV') === 'production';
$app = new QuickRouter(
    cache: $isProduction,
    di: true
);

// Старый стиль тоже работает
$app = new QuickRouter([
    'cache' => $isProduction,
    'di' => true
]);
```

---

## 🆚 QuickRouter vs Router

| Аспект | QuickRouter | Router |
|--------|-------------|--------|
| **Сложность** | Минимальная | Средняя |
| **API** | Простой | Полный |
| **DI** | Опционально | Да |
| **Cache** | Опционально | Да |
| **Middleware** | Да | Да |
| **Для новичков** | ✅ Да | ⚠️ Сложновато |
| **Для production** | ✅ Да (до 100 роутов) | ✅ Да (любой масштаб) |

**Когда использовать QuickRouter:**
- Учебные проекты
- Лендинги
- Простые сайты
- Прототипы
- До 100 маршрутов

**Когда использовать Router:**
- Enterprise приложения
- Сложная архитектура
- 100+ маршрутов
- Нужен полный контроль

---

## 🚨 Частые ошибки

### Ошибка 1: "No match"
```php
// ❌ Неправильно - не добавлен маршрут
$app = new QuickRouter();
$app->run(); // Ошибка!

// ✅ Правильно
$app = new QuickRouter();
$app->get('/', fn() => "Hello!");
$app->run();
```

### Ошибка 2: Забыли вызвать `run()`
```php
// ❌ Неправильно
$app = new QuickRouter();
$app->get('/', fn() => "Hello!");
// Ничего не работает!

// ✅ Правильно
$app = new QuickRouter();
$app->get('/', fn() => "Hello!");
$app->run(); // Обязательно!
```

### Ошибка 3: Неправильный формат handler
```php
// ❌ Неправильно
$app->get('/', UserController::class); // Не хватает метода!

// ✅ Правильно - массив [класс, метод]
$app->get('/', [UserController::class, 'index']);

// ✅ Или callable
$app->get('/', fn() => "Hello!");
```

---

## 📚 Следующие шаги

1. **Изучите примеры:**
   - `examples/quick-example.php` - базовые возможности
   - `examples/rest-api-example.php` - REST API
   - `examples/middleware-example.php` - middleware

2. **Прочитайте документацию:**
   - [CONCEPT.md](CONCEPT.md) - философия проекта
   - [README.md](../README.md) - полная документация

3. **Когда вырастете:**
   - Изучите полный `Router` для сложных проектов
   - Используйте `$app->advanced()` для доступа к продвинутым функциям

---

## 💬 Помощь и поддержка

- **Вопросы:** GitHub Issues
- **Email:** victor.faust.dev@gmail.com
- **Примеры:** `examples/` директория

---

## ✨ Заключение

**QuickRouter** - это самый простой способ начать использовать роутинг в PHP!

```php
$app = new QuickRouter();
$app->get('/', fn() => "That's all! 🎉");
$app->run();
```

Удачи в разработке! 🚀

---

**Версия:** 1.0  
**Дата:** 3 октября 2025  
**Статус:** v2.0-alpha

