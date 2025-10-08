# Концепция и философия Router

> Видение проекта, целевая аудитория и стратегия развития

**Дата:** 3 октября 2025  
**Версия:** 1.0  
**Статус:** v2.0-alpha

---

## 🎯 Миссия проекта

**Router** - это легковесный PHP роутер для **мелких и простых проектов**, которым не нужна тяжеловесная Symfony или Laravel, но нужно больше чем просто `switch($_SERVER['REQUEST_URI'])`.

### Ключевая идея
> "Быть как **Slim Framework**, но еще проще и понятнее для новичков"

---

## 👥 Целевая аудитория

### **Основная аудитория:**

1. **Junior разработчики**
   - Изучают PHP и MVC паттерн
   - Нужен простой роутер для учебных проектов
   - Хотят понять как работает маршрутизация изнутри

2. **Фрилансеры**
   - Делают лендинги, небольшие корпоративные сайты
   - Нужен быстрый старт без лишних зависимостей
   - 10-50 маршрутов максимум

3. **Стартапы/MVP**
   - Прототипируют идею
   - Нужна гибкость без оверхеда больших фреймворков
   - Быстрая разработка важнее enterprise-фичей

4. **Legacy проекты**
   - Миграция со старого кода на современный
   - Нельзя переписать всё на Symfony сразу
   - Нужен простой роутер как первый шаг

### **НЕ целевая аудитория:**

❌ Проекты на Symfony/Laravel - у них свой роутер  
❌ Enterprise приложения - нужны более мощные решения  
❌ High-load проекты - нужна специфическая оптимизация  
❌ Микросервисы - лучше использовать готовые фреймворки

---

## 💡 Философия

### 1. **Простота превыше всего**

```php
// Плохо: сложная инициализация
$collections = new RoutesCollection();
$config = new Config();
$config->setRunner(new Runner());
$router = new Router();
$router->setConfig($config);
$router->setCollection($collections);

// Хорошо: работает из коробки
$router = Router::create();
$router->get('/', HomeController::class, 'index');
$router->run();
```

### 2. **Convention over Configuration**

Умные дефолты избавляют от лишнего кода:

```php
// Не нужно явно указывать GET, если это очевидно
$router->get('/users', UserController::class, 'index');

// Автоматическое определение типов параметров
public function show(int $id) // id автоматически int, не string!
```

### 3. **Progressive Enhancement**

Роутер растет вместе с проектом:

```
Beginner    → "Hello World" за 5 минут
     ↓
Intermediate → Контроллеры, группы роутов
     ↓
Advanced    → Middleware, DI, кеширование
```

### 4. **Читаемость > Краткость**

```php
// Можно кратко, но непонятно
$r->r('/u/{i}', UC::class, 's');

// Лучше многословно, но понятно
$router->get('/users/{id}', UserController::class, 'show');
```

### 5. **Магия в меру**

```php
// ✅ Хорошая магия: автоинъекция параметров
public function show($id) // $id автоматически из URL

// ✅ Хорошая магия: авто type casting
public function show(int $id) // автоматически валидируется

// ❌ Плохая магия: слишком неявно
public function show() {
    $id = magic_get_param(); // откуда? как?
}
```

---

## 📋 Принципы дизайна

### ✅ **ДА (что делаем)**

- **Простой API** для 90% use cases
- **Автоматическая магия** для рутинных задач
- **Отличная документация** с примерами
- **Готовый starter template**
- **Zero configuration** для быстрого старта
- **Опциональные** продвинутые фичи
- **Совместимость** с PHP 8.1+

### ❌ **НЕТ (что НЕ делаем)**

- **Не строим фреймворк** - только роутинг
- **Не конкурируем** с Symfony/Laravel
- **Не добавляем** ORM, template engine, etc
- **Не усложняем** API ради редких случаев
- **Не требуем** знания DI, PSR-7, etc для старта
- **Не жертвуем** простотой ради производительности
- **Не поддерживаем** древние версии PHP

---

## 🎨 Текущее состояние vs Концепция

### ✅ **Что уже соответствует концепции**

#### 1. Понятный декларативный API
```php
Route::create('/users/{id}', UserController::class, 'show', [], ['GET'])
```
**Статус:** ✅ Отлично для целевой аудитории

#### 2. Автоинъекция параметров из URL
```php
public function show($id) // магия работает!
```
**Статус:** ✅ Именно то, что нужно

#### 3. Поддержка анонимных функций
```php
$route = RouteAnonymousFunc::create('/hello/{name}', fn($name) => "Hello, $name");
```
**Статус:** ✅ Идеально для прототипирования

#### 4. Группировка роутов
```php
$routes->prefix('/api')->group(function($group) {
    $group->get('/users', UserController::class, 'index');
});
```
**Статус:** ✅ Удобно даже для малых проектов

#### 5. Чистый код
- PHPStan level 5 ✅
- PSR-12 compliant ✅
- Хорошая структура ✅

---

### ⚠️ **Что избыточно для концепции**

#### 1. Обязательный Dependency Injection

**Текущая ситуация:**
```php
$router->enableDI(function($container) {
    $container->singleton(UserService::class);
});
```

**Проблема:**
- Новички не знают что такое DI
- Для 10-50 роутов контейнер не нужен
- Усложняет Quick Start

**Решение:**
```php
// DI должен быть ОПЦИОНАЛЬНЫМ
// Без DI роутер работает из коробки
$router = Router::create(); // DI отключен
$router->get('/', HomeController::class, 'index');

// DI включается явно для Advanced use cases
$router = Router::create()->withDI();
```

**Приоритет:** 🟡 Средний  
**Сложность:** Низкая  
**Время:** 2-4 часа

---

#### 2. Система кеширования по умолчанию

**Текущая ситуация:**
```php
$router->enableCache();
$router->setCache(new FileCache('cache/routes'));
$router->getConfig()->setCacheTtl(3600);
```

**Проблема:**
- Для 10-50 роутов прирост производительности ~0ms
- Создает файлы на диске
- Усложняет API

**Решение:**
```php
// Кеш ОТКЛЮЧЕН по умолчанию
$router = Router::create(); // no cache

// Включается явно для production
if ($isProduction) {
    $router->enableCache();
}

// Или через environment detection
$router = Router::create([
    'cache' => getenv('APP_ENV') === 'production'
]);
```

**Рекомендация в документации:**
> "Кеширование нужно только если у вас 500+ маршрутов. Для мелких проектов (до 100 роутов) кеш не даст заметного прироста производительности."

**Приоритет:** 🟡 Средний  
**Сложность:** Низкая  
**Время:** 1-2 часа

---

#### 3. Многословная валидация

**Текущая ситуация:**
```php
$route->validate([
    ParameterValidationRule::create('id')
        ->required()
        ->addValidator(new IntValidator())
        ->addValidator(new RangeValidator(), ['min' => 1, 'max' => 1000])
]);
```

**Проблема:**
- Слишком многословно для простых случаев
- Нужно знать все классы валидаторов
- Отпугивает новичков

**Решение 1: Laravel-style строки**
```php
$route->validate([
    'id' => 'required|int|min:1|max:1000',
    'email' => 'required|email',
    'age' => 'int|between:18,120'
]);
```

**Решение 2: Type hints (автоматическая валидация)**
```php
// Просто указываем тип в методе контроллера
public function show(int $id) 
{
    // $id уже int и валидирован!
    // Если передано не int - автоматически 400 Bad Request
}
```

**Приоритет:** 🟢 Высокий (для UX)  
**Сложность:** Средняя  
**Время:** 4-8 часов

---

### ❌ **Что отсутствует для концепции**

#### 1. "Hello World" за 5 минут ⚠️

**Проблема:** Нет супер-простого Quick Start

**Текущий README:**
```php
// 10+ строк кода для первого роута
$collections = new RoutesCollection();
$collections->set(Route::create('/', TestController::class, 'index', [], ['GET']));
$config = new Config();
$config->setRunner(new Runner());
$router = new Router();
$router->setConfig($config);
$router->setCollection($collections);
$router->run();
```

**Должно быть:**
```php
// index.php - полное приложение в 8 строк
<?php
require 'vendor/autoload.php';

$app = Router::quick();

$app->get('/', fn() => "Hello World!");
$app->get('/users/{id}', fn($id) => "User #$id");

$app->run();
```

**Реализация:**
```php
// src/Router.php - добавить статический метод
public static function quick(): self
{
    $router = new self();
    $router->setCollection(new RoutesCollection());
    $router->setConfig(new Config());
    // Все настроено автоматически!
    return $router;
}

// Синтаксический сахар для популярных методов
public function get(string $uri, $handler): self
{
    if (is_callable($handler)) {
        $route = RouteAnonymousFunc::create($uri, $handler, ['GET']);
    } else {
        // $handler = [Controller::class, 'method']
        $route = Route::create($uri, $handler[0], $handler[1], [], ['GET']);
    }
    
    $this->collections->set($route);
    return $this;
}

// Аналогично для post(), put(), delete(), patch()
```

**Приоритет:** 🔴 Критический (для концепции!)  
**Сложность:** Низкая  
**Время:** 2-3 часа

---

#### 2. Готовый Starter Template ⚠️

**Проблема:** Новичок не знает как структурировать проект

**Должно быть:**
```bash
# Быстрый старт нового проекта
composer create-project faustvik/router-skeleton my-app
cd my-app
php -S localhost:8000
# Открыть http://localhost:8000 - работает!
```

**Структура skeleton:**
```
router-skeleton/
├── public/
│   ├── index.php              # Entry point
│   ├── .htaccess             # Apache config
│   └── assets/
│       ├── css/
│       └── js/
├── app/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   └── UserController.php
│   ├── Models/              # (optional, примеры)
│   └── Views/               # (optional, примеры)
├── routes/
│   └── web.php              # Все роуты здесь
├── config/
│   └── app.php              # Конфигурация
├── .env.example
├── .gitignore
├── composer.json
└── README.md                # Инструкции для проекта
```

**public/index.php:**
```php
<?php
require __DIR__ . '/../vendor/autoload.php';

// Загружаем конфиг
$config = require __DIR__ . '/../config/app.php';

// Создаем роутер
$router = Router::create($config);

// Загружаем роуты
require __DIR__ . '/../routes/web.php';

// Запускаем
$router->run();
```

**routes/web.php:**
```php
<?php
use App\Controllers\HomeController;
use App\Controllers\UserController;

// Домашняя страница
$router->get('/', HomeController::class, 'index');

// Пользователи
$router->get('/users', UserController::class, 'index');
$router->get('/users/{id}', UserController::class, 'show');

// API группа
$router->prefix('/api')->group(function($api) {
    $api->get('/users', UserController::class, 'apiIndex');
    $api->get('/users/{id}', UserController::class, 'apiShow');
});
```

**Приоритет:** 🟠 Высокий (для UX новичков)  
**Сложность:** Средняя  
**Время:** 4-6 часов

---

#### 3. Автоматический Type Casting ⚠️

**Текущая ситуация:**
```php
public function show($id) 
{
    // $id это string! "123"
    $userId = (int) $id; // Нужно кастить вручную
}
```

**Должно быть:**
```php
public function show(int $id) 
{
    // $id уже int! 123
    // Если передано не число - автоматически 400 Bad Request
}
```

**Реализация в Runner.php:**
```php
// src/Router/Components/Runner.php
private function castParameter(mixed $value, \ReflectionParameter $param): mixed
{
    $type = $param->getType();
    
    if (!$type instanceof \ReflectionNamedType) {
        return $value;
    }
    
    return match($type->getName()) {
        'int' => is_numeric($value) ? (int) $value : 
            throw new ValidationException('Parameter must be integer'),
        'float' => is_numeric($value) ? (float) $value : 
            throw new ValidationException('Parameter must be float'),
        'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        'string' => (string) $value,
        default => $value
    };
}

// Использование при вызове контроллера
foreach ($method->getParameters() as $param) {
    $paramName = $param->getName();
    
    if (isset($params[$paramName])) {
        $value = $this->castParameter($params[$paramName], $param);
        $atr[] = $value;
    }
}
```

**Приоритет:** 🟢 Высокий (магия!)  
**Сложность:** Средняя  
**Время:** 3-4 часа

---

#### 4. Resource Routes (Convention-based)

**Проблема:** Для CRUD операций много повторяющегося кода

**Текущий подход:**
```php
$router->get('/users', UserController::class, 'index');
$router->get('/users/create', UserController::class, 'create');
$router->post('/users', UserController::class, 'store');
$router->get('/users/{id}', UserController::class, 'show');
$router->get('/users/{id}/edit', UserController::class, 'edit');
$router->put('/users/{id}', UserController::class, 'update');
$router->delete('/users/{id}', UserController::class, 'destroy');
// 7 роутов!
```

**Должно быть:**
```php
// Один вызов для стандартного REST API
$router->resource('users', UserController::class);

// Автоматически создает:
// GET    /users          -> index()
// GET    /users/create   -> create()
// POST   /users          -> store()
// GET    /users/{id}     -> show($id)
// GET    /users/{id}/edit -> edit($id)
// PUT    /users/{id}     -> update($id)
// DELETE /users/{id}     -> destroy($id)
```

**Приоритет:** 🟡 Средний (nice to have)  
**Сложность:** Средняя  
**Время:** 3-4 часа

---

## 🚀 Стратегия развития

### Фаза 1: Упрощение (Sprint 1-2, 4-6 недель)

**Цель:** Сделать роутер действительно простым

**Задачи:**
- [ ] Добавить `Router::quick()` метод
- [ ] Добавить методы `get()`, `post()`, etc в Router
- [ ] Реализовать автоматический type casting
- [ ] Упростить API валидации (строки вместо объектов)
- [ ] Сделать DI опциональным (отключен по умолчанию)
- [ ] Сделать Cache опциональным (отключен по умолчанию)

**Метрика успеха:** Hello World в 8 строк кода

---

### Фаза 2: Готовый шаблон (Sprint 3, 1-2 недели)

**Цель:** Новичок может начать за 5 минут

**Задачи:**
- [ ] Создать репозиторий `faustvik/router-skeleton`
- [ ] Настроить структуру проекта
- [ ] Добавить примеры контроллеров
- [ ] Написать подробный README для skeleton
- [ ] Опубликовать на Packagist
- [ ] Добавить в README основного проекта

**Метрика успеха:** `composer create-project` → работает за 1 минуту

---

### Фаза 3: Улучшение DX (Sprint 4-5, 2-3 недели)

**Цель:** Приятно использовать

**Задачи:**
- [ ] Добавить `resource()` метод для REST
- [ ] Улучшить error messages (понятные для новичков)
- [ ] Добавить helper функции (`route()`, `url()`)
- [ ] Реализовать named routes
- [ ] Добавить генерацию URL по имени роута

**Метрика успеха:** Минимум кода для типичных задач

---

### Фаза 4: Документация (Sprint 6, 1-2 недели)

**Цель:** Лучшая документация в категории

**Задачи:**
- [ ] Переписать README (фокус на Quick Start)
- [ ] Создать "Getting Started" туториал (5-10 минут)
- [ ] Добавить "Cookbook" (типичные задачи)
- [ ] Записать видео-туториал (YouTube)
- [ ] Создать сайт документации (GitHub Pages)
- [ ] Перевести на английский

**Метрика успеха:** Новичок понимает за 10 минут чтения

---

### Фаза 5: Продвинутые фичи (Sprint 7+, опционально)

**Цель:** Расти вместе с проектом

**Задачи (опциональные):**
- [ ] Rate Limiting middleware
- [ ] CSRF protection
- [ ] Session management
- [ ] Simple Auth (Basic/Bearer)
- [ ] API versioning
- [ ] OpenAPI/Swagger генерация

**Метрика успеха:** Подходит для проектов до 200-300 роутов

---

## 📊 Целевые метрики

### **Developer Experience (DX)**

| Метрика | Текущее | Цель | Статус |
|---------|---------|------|--------|
| Время до Hello World | 15+ минут | 5 минут | 🔴 |
| Строк кода для старта | 25+ | 8-10 | 🔴 |
| Concepts для изучения | 5-7 | 2-3 | 🟡 |
| Понятность ошибок | 5/10 | 9/10 | 🟡 |
| Качество документации | 6/10 | 9/10 | 🟡 |

### **Technical Excellence**

| Метрика | Текущее | Цель | Статус |
|---------|---------|------|--------|
| Test Coverage | 0% | 80%+ | 🔴 |
| PHPStan Level | 5 | 8 | 🟡 |
| Security Score | 3/10 | 9/10 | 🔴 |
| Performance | Good | Good | 🟢 |
| Code Style | PSR-12 ✓ | PSR-12 ✓ | 🟢 |

### **Community & Adoption**

| Метрика | Текущее | Цель (6 мес) | Статус |
|---------|---------|--------------|--------|
| GitHub Stars | ? | 100+ | - |
| Weekly Downloads | ? | 500+ | - |
| Contributors | 1 | 5+ | 🔴 |
| Issues/PRs | 0 | 10+ active | 🔴 |
| Tutorial Views | 0 | 1000+ | 🔴 |

---

## 🎓 Позиционирование vs Конкуренты

### Slim Framework
```
Slim:   Микрофреймворк, PSR-7, более сложный
Router: Только роутинг, проще, быстрее старт
```
**Наше преимущество:** Еще проще и легче

### Laravel Router (standalone)
```
Laravel: Мощный, но тяжелый, много зависимостей
Router:  Легкий, минимум зависимостей
```
**Наше преимущество:** Легковесность

### FastRoute (nikic)
```
FastRoute: Очень быстрый, низкоуровневый
Router:    Менее быстрый, но удобнее API
```
**Наше преимущество:** Developer Experience

### Symfony Router
```
Symfony: Enterprise-grade, сложная настройка
Router:  Simple projects, easy setup
```
**Наше преимущество:** Простота

### **Наша ниша:**
```
                 Сложность
                     ↑
     Symfony/Laravel |
                     |
     Slim Framework  |
                     |
     Router ←——————— | [ЗДЕСЬ МЫ]
                     |
     FastRoute       |
                     |
     switch/case     |
                     ↓
                 Простота
```

---

## 💎 Ключевые дифференциаторы

### 1. **Прогрессивное усложнение**
```
Level 1: Hello World (8 строк)
Level 2: Контроллеры (30-50 строк)
Level 3: Middleware, DI (100+ строк)
```
**Уникально:** Растем вместе с навыками пользователя

### 2. **Автоматическая магия**
```php
// Просто укажите тип - всё остальное автоматически
public function show(int $id) // валидация + каст автоматом!
```
**Уникально:** Меньше boilerplate кода

### 3. **Zero Configuration**
```php
// Работает из коробки, без настройки
$router = Router::quick();
$router->run();
```
**Уникально:** Не нужно понимать архитектуру для старта

### 4. **Готовый стартер**
```bash
composer create-project faustvik/router-skeleton my-app
# Готово! Проект работает
```
**Уникально:** От идеи до кода за 1 минуту

---

## 🎯 Принятие решений

При добавлении новой функциональности задавайте вопросы:

### ✅ Добавлять, если:
1. Нужно в 80%+ проектов целевой аудитории
2. Упрощает жизнь новичкам
3. Не усложняет Quick Start
4. Можно сделать опциональным
5. Не требует глубоких знаний PHP

### ❌ НЕ добавлять, если:
1. Нужно только в enterprise проектах
2. Требует изучения сложных концепций
3. Усложняет API для всех ради 5% кейсов
4. Есть готовые библиотеки (не изобретаем велосипеды)
5. Делает роутер "фреймворком"

### 🤔 Примеры решений:

**Добавить ли ORM?**
- ❌ НЕТ - это не роутинг, есть готовые решения

**Добавить ли Middleware?**
- ✅ ДА - типичная задача, упрощает код

**Добавить ли WebSocket support?**
- ❌ НЕТ - редкий кейс, сложная реализация

**Добавить ли Rate Limiting?**
- 🤔 ОПЦИОНАЛЬНО - полезно, но не для всех

**Добавить ли resource routes?**
- ✅ ДА - типичный паттерн, упрощает CRUD

---

## 📝 Манифест проекта

### **Мы верим что:**

1. **Простота важнее мощности** для целевой аудитории
2. **DX > Performance** для проектов до 100k RPS
3. **Convention > Configuration** экономит время
4. **Магия допустима** если она предсказуема
5. **Документация = часть продукта**, не опция
6. **Beginner-friendly** не значит "примитивный"
7. **Open Source** должен быть доступен всем

### **Мы НЕ верим что:**

1. Больше фич = лучше продукт
2. Enterprise паттерны нужны всем
3. Скорость важнее читаемости кода
4. Документация может подождать
5. Backwards compatibility важнее прогресса (для alpha/beta)

---

## 🔮 Видение будущего (v3.0+)

### **Что может быть в будущем:**

1. **Visual Route Builder** (веб-интерфейс для дизайна роутов)
2. **Auto-generated Admin Panel** на основе роутов
3. **AI-powered route suggestions** (анализ кода → предложения)
4. **Performance monitoring** встроенный
5. **GraphQL support** (опционально)
6. **Serverless deployment** helpers

### **Чего точно не будет:**

- Встроенная ORM
- Template engine
- Full-stack framework
- CLI для всего подряд
- Совместимость с PHP 5.x

---

## 📞 Обратная связь

Эта концепция - живой документ. Если вы:

- **Не согласны** с какими-то решениями
- **Видите** недостающие аспекты
- **Имеете идеи** по улучшению
- **Используете** роутер в своем проекте

**Свяжитесь с нами:**
- GitHub Issues (тег `concept`)
- Email: victor.faust.dev@gmail.com
- Discussions в репозитории

---

## 🗓️ История изменений

| Дата | Версия | Изменения |
|------|--------|-----------|
| 2025-10-03 | 1.0 | Первая версия документа концепции |

---

**Создано:** 3 октября 2025  
**Автор:** Victor + Community  
**Статус:** Living Document (будет обновляться)

---

> "The best code is no code at all. The second best is code that's so simple, it's obviously correct."
> 
> — Древняя мудрость программистов

