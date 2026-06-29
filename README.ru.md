# PHP Router

Современный, легковесный PHP Router с поддержкой middleware, dependency injection и кеширования.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

[🇬🇧 English version](README.md)

## ✨ Возможности

- 🚀 **Быстрый и легковесный** - Оптимизированное сопоставление маршрутов с O(1) поиском для статических маршрутов
- 🎯 **Динамические URL параметры** - Поддержка паттернов `/users/{id}` с ограничениями
- 🔌 **Поддержка Middleware** - Встроенный стек middleware с популярными реализациями
- 💉 **Dependency Injection** - Автоматическое разрешение зависимостей для контроллеров
- 💾 **Кеширование маршрутов** - Повышение производительности в 2-10 раз в production
- 🏷️ **Именованные маршруты** - Генерация URL по имени маршрута
- 📦 **Группы маршрутов** - Организация маршрутов с префиксами и общим middleware
- 🎭 **Анонимные функции** - Использование замыканий как обработчиков маршрутов
- 🔒 **Безопасность** - Встроенные middleware для CSRF, CORS, Auth и Rate Limiting
- 📝 **PSR-совместимость** - Чистый, современный PHP 8.1+ код

## 📋 Требования

- PHP 8.1 или выше

## 📥 Установка

```bash
composer require faustvik/router
```

## 🚀 Быстрый старт

### Вариант 1: QuickRouter (Рекомендуется для начинающих)

```php
use FaustVik\Router\Router\QuickRouter;

$app = new QuickRouter();

// Простой маршрут
$app->get('/', fn() => "Hello World!");

// Маршрут с параметрами
$app->get('/users/{id}', fn($id) => "Пользователь #$id");

// Маршрут с контроллером
$app->get('/posts', [PostController::class, 'index']);

$app->run();
```

### Вариант 2: Полный Router (Расширенные возможности)

```php
use FaustVik\Router\Router\Router;
use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RoutesCollection;

$routes = new RoutesCollection();

// Добавляем маршруты
$routes->addGet(
    Route::create('/', HomeController::class, 'index', methods: ['GET'])
        ->name('home')
);

$routes->addGet(
    Route::create('/users/{id}', UserController::class, 'show', methods: ['GET'])
        ->where('id', '\d+')
        ->name('users.show')
);

$router = new Router();
$router->setCollection($routes);
$router->run();
```

## 📖 Основные концепции

### Динамические URL параметры

```php
// Базовый параметр
$app->get('/users/{id}', fn($id) => "Пользователь #$id");

// Множественные параметры
$app->get('/posts/{year}/{month}', fn($year, $month) => 
    "Архив: $year-$month"
);

// Опциональные параметры
$app->get('/posts/{year?}/{month?}', [PostController::class, 'archive']);

// С ограничениями
Route::create('/users/{id}', UserController::class, 'show')
    ->where('id', '\d+')  // Только цифры
    ->where('slug', '[a-z0-9\-]+');  // Буквы, цифры и дефисы
```

### Middleware

```php
use FaustVik\Router\Middleware\AuthMiddleware;
use FaustVik\Router\Middleware\CorsMiddleware;
use FaustVik\Router\Middleware\LoggingMiddleware;

// Middleware для конкретного маршрута
$route = Route::create('/admin/users', AdminController::class, 'index')
    ->middleware([AuthMiddleware::class, LoggingMiddleware::class]);

// Глобальный middleware (применяется ко всем маршрутам)
$app->addMiddleware(CorsMiddleware::class)
    ->addMiddleware(LoggingMiddleware::class);

// Группа с middleware
$app->middleware([AuthMiddleware::class], function($app) {
    $app->get('/admin', [AdminController::class, 'index']);
    $app->get('/profile', [ProfileController::class, 'show']);
});
```

#### Встроенные Middleware

- **AuthMiddleware** - Аутентификация по Bearer токену
- **CorsMiddleware** - CORS заголовки и preflight запросы
- **CsrfMiddleware** - Защита от CSRF атак
- **RateLimitMiddleware** - Ограничение частоты запросов
- **LoggingMiddleware** - Логирование HTTP запросов

#### Собственный Middleware

```php
use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\interfaces\Middleware\MiddlewareInterface;

class CustomMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // До выполнения маршрута
        
        $response = $next($request);
        
        // После выполнения маршрута
        
        return $response;
    }
}
```

### Кеширование маршрутов

```php
use FaustVik\Router\Cache\FileCache;

$router = new Router();

// Включаем кеширование (повышение производительности в 2-10 раз)
$router->enableCache();

// Настраиваем кеш
$cache = new FileCache('cache/routes', 'app_');
$router->setCache($cache);
$router->getConfig()->setCacheTtl(3600); // 1 час

// Очищаем кеш
$router->clearRouteCache();
```

**Производительность**: Кеширование может ускорить обработку запросов в 2-10 раз, особенно при большом количестве маршрутов.

### Именованные маршруты и генерация URL

```php
// Определяем именованные маршруты
Route::create('/users/{id}', UserController::class, 'show')
    ->name('users.show');

Route::create('/posts/{year}/{slug}', PostController::class, 'show')
    ->name('posts.show');

// Генерируем URL
$router->url('users.show', ['id' => 123]);
// => /users/123

$router->url('posts.show', [
    'year' => 2025,
    'slug' => 'my-post'
], ['ref' => 'twitter']);
// => /posts/2025/my-post?ref=twitter

// Проверяем существование маршрута
if ($router->has('users.show')) {
    $url = $router->url('users.show', ['id' => 123]);
}
```

### Группы маршрутов

```php
// Группы с префиксом
$app->prefix('/api', function($app) {
    $app->get('/users', [UserController::class, 'index']);
    $app->get('/posts', [PostController::class, 'index']);
});
// Создаст: /api/users и /api/posts

// С RoutesCollection
$routes->prefix('/admin')->group(function($group) {
    $group->get('/users', UserController::class, 'index')
        ->name('admin.users.index');
    
    $group->get('/settings', SettingsController::class, 'index')
        ->name('admin.settings');
});
```

### Dependency Injection

```php
// Включаем DI
$router->enableDI();

// Настраиваем привязки
$router->enableDI(function($container) {
    $container->singleton(DatabaseInterface::class, MySQLDatabase::class);
    $container->bind(LoggerInterface::class, FileLogger::class);
});

// Контроллеры с зависимостями
class UserController
{
    public function __construct(
        private DatabaseInterface $db,
        private LoggerInterface $logger
    ) {}
    
    public function show($id): Response
    {
        $user = $this->db->find('users', $id);
        $this->logger->info("Просмотр пользователя: $id");
        
        return Response::json($user);
    }
}
```

### Request и Response

```php
use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;

class UserController
{
    public function show(Request $request, $id): Response
    {
        // Получаем query параметры
        $page = $request->getQueryParam('page', 1);
        
        // Получаем JSON body
        $data = $request->input('name');
        
        // Получаем заголовки
        $token = $request->getHeader('Authorization');
        
        // Возвращаем JSON ответ
        return Response::json([
            'user' => ['id' => $id, 'name' => 'Иван'],
            'page' => $page
        ]);
    }
    
    public function create(Request $request): Response
    {
        // Обработка загрузки файлов
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            move_uploaded_file($file['tmp_name'], 'uploads/' . $file['name']);
        }
        
        // Редирект
        return Response::redirect('/users');
    }
}
```

## 📚 Примеры

Комплексные примеры, демонстрирующие возможности роутера от базовых до продвинутых:

### 🟢 Начальный уровень
- **[quick-example.php](examples/quick-example.php)** - Введение в QuickRouter (старт за 5 минут)
- **[basic-example.php](examples/basic-example.php)** - Полный роутер с контроллерами
- **[url-parameters-example.php](examples/url-parameters-example.php)** - Работа с URL параметрами

### 🟡 Средний уровень  
- **[middleware-example.php](examples/middleware-example.php)** - Основы middleware
- **[response-types-example.php](examples/response-types-example.php)** - JSON, HTML, редиректы
- **[rest-api-example.php](examples/rest-api-example.php)** - Полный REST API с CRUD
- **[named-routes-example.php](examples/named-routes-example.php)** - Генерация URL

### 🟠 Продвинутый уровень
- **[di-basic-example.php](examples/di-basic-example.php)** - Dependency injection
- **[cache-example.php](examples/cache-example.php)** - Кеширование маршрутов
- **[error-handling-example.php](examples/error-handling-example.php)** - Обработка ошибок
- **[csrf-protection-example.php](examples/csrf-protection-example.php)** - CSRF защита

### Быстрое тестирование
```bash
cd examples

# Тест базовой маршрутизации
php quick-example.php

# Тест с конкретным маршрутом
REQUEST_URI="/users/123" php basic-example.php

# Тест REST API
REQUEST_URI="/api/users" php rest-api-example.php
```

См. **[examples/README.md](examples/README.md)** для полной документации.

## 🏗️ Архитектура

### Компоненты

- **Router** - Основной класс роутера с сопоставлением и выполнением маршрутов
- **Route** - Определение маршрута с классом контроллера
- **RouteAnonymousFunc** - Маршрут с обработчиком-замыканием
- **RoutesCollection** - Реестр и управление маршрутами
- **Config** - Конфигурация компонентов роутера
- **MiddlewareStack** - Реализация паттерна Chain of Responsibility

### Стратегии сопоставления

- **Matching** - Базовое сопоставление маршрутов
- **OptimizedMatching** - Индексированное сопоставление с O(1) поиском статических маршрутов
- **CachedMatching** - Обертка с кешированием результатов сопоставления

## 🧪 Тестирование

```bash
# Запуск тестов
composer test

# Запуск с покрытием
composer test-coverage

# Статический анализ
composer phpstan
```

## 📖 Документация

- [Быстрый старт](docs/QUICK_START.md)
- [Концепция и архитектура](docs/CONCEPT.md)
- [Реализация DI и Middleware](docs/DI_MIDDLEWARE_IMPLEMENTATION.md)
- [Планы развития](docs/FUTURE_PLANS.md)

## 🤝 Вклад в проект

Приветствуются вклады! Эта библиотека разработана так, чтобы быть доступной как для начинающих, так и для опытных разработчиков. Мы не пытаемся заменить роутеры Symfony или Yii2, а предоставляем легковесную, понятную альтернативу.

## 📄 Лицензия

Лицензия MIT. См. файл [LICENSE](LICENSE) для деталей.

## 🌟 Благодарности

Создано с ❤️ для PHP сообщества.

