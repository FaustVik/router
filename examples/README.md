# FaustVik Router Examples

Полная коллекция примеров, демонстрирующих возможности роутера от базовых до продвинутых.

## 📂 Структура примеров

### 🟢 Базовые примеры (для начинающих)

#### [basic-example.php](basic-example.php)
**Введение в роутер**
- Создание простых маршрутов
- Использование контроллеров и анонимных функций
- URL параметры `{id}`, `{name}`
- Разные HTTP методы (GET, POST, PUT, DELETE)
- Работа с Request и Response объектами

```bash
# Тестирование:
REQUEST_URI="/" php basic-example.php
REQUEST_URI="/users/123" php basic-example.php
REQUEST_URI="/hello/John" php basic-example.php
```

#### [url-parameters-example.php](url-parameters-example.php)
**Продвинутая работа с параметрами URL**
- Одиночные и множественные параметры
- Приоритет параметров над query string
- Валидация параметров
- Глубокая вложенность параметров
- Инжекция Request объекта

```bash
# Тестирование:
REQUEST_URI="/blog/technology/123" php url-parameters-example.php
REQUEST_URI="/users/456/posts/789/comments/101" php url-parameters-example.php
REQUEST_URI="/validation/abc" php url-parameters-example.php
```

### 🟡 Средние примеры

#### [response-types-example.php](response-types-example.php)
**Различные типы ответов**
- JSON ответы с разными статус-кодами
- HTML страницы (полные и фрагменты)
- Редиректы (временные и постоянные)
- Кастомные заголовки
- XML, Plain Text, CORS
- Симуляция скачивания файлов

```bash
# Тестирование:
REQUEST_URI="/api/users" php response-types-example.php
REQUEST_URI="/" php response-types-example.php
REQUEST_URI="/custom-headers" php response-types-example.php
REQUEST_URI="/status/404" php response-types-example.php
```

### 🟠 Продвинутые примеры

#### [rest-api-example.php](rest-api-example.php)
**Полноценный REST API**
- Полный CRUD для пользователей и постов
- Симуляция базы данных в памяти
- Связи между ресурсами (пользователи → посты)
- Валидация данных
- Правильные HTTP статус-коды
- Документация API

```bash
# Тестирование:
REQUEST_URI="/api" php rest-api-example.php
REQUEST_URI="/api/users" php rest-api-example.php
REQUEST_URI="/api/users/1/posts" php rest-api-example.php
REQUEST_METHOD="POST" REQUEST_URI="/api/users" php rest-api-example.php
```

#### [error-handling-example.php](error-handling-example.php)
**Комплексная обработка ошибок**
- PHP ошибки (undefined index, division by zero)
- Кастомные исключения (ValidationException, etc.)
- HTTP статус-коды (401, 403, 404, 429, 500)
- Ограничения HTTP методов
- Детальная информация об ошибках
- Глобальный обработчик ошибок

```bash
# Тестирование:
REQUEST_URI="/errors/validation" php error-handling-example.php
REQUEST_URI="/errors/auth" php error-handling-example.php
REQUEST_METHOD="POST" REQUEST_URI="/methods/get-only" php error-handling-example.php
```

## 🚀 Быстрый старт

### 1. Базовое знакомство
```bash
cd examples
php basic-example.php
REQUEST_URI="/users/123" php basic-example.php
```

### 2. Изучение параметров
```bash
REQUEST_URI="/blog/technology/456" php url-parameters-example.php
```

### 3. Типы ответов
```bash
REQUEST_URI="/api/users" php response-types-example.php
REQUEST_URI="/" php response-types-example.php
```

### 4. REST API
```bash
REQUEST_URI="/api" php rest-api-example.php
REQUEST_URI="/api/users" php rest-api-example.php
```

### 5. Обработка ошибок
```bash
REQUEST_URI="/errors/validation" php error-handling-example.php
```

## 🧪 Продвинутое тестирование

### Тестирование разных HTTP методов
```bash
# GET запросы
REQUEST_METHOD="GET" REQUEST_URI="/api/users" php rest-api-example.php

# POST запросы  
REQUEST_METHOD="POST" REQUEST_URI="/api/users" php rest-api-example.php

# PUT запросы
REQUEST_METHOD="PUT" REQUEST_URI="/api/users/1" php rest-api-example.php

# DELETE запросы
REQUEST_METHOD="DELETE" REQUEST_URI="/api/users/1" php rest-api-example.php
```

### Тестирование ошибок
```bash
# Ошибки валидации
REQUEST_URI="/errors/validation" php error-handling-example.php

# Ошибки авторизации
REQUEST_URI="/errors/auth" php error-handling-example.php

# Несуществующие маршруты
REQUEST_URI="/non-existent" php error-handling-example.php

# Неправильные HTTP методы
REQUEST_METHOD="POST" REQUEST_URI="/methods/get-only" php error-handling-example.php
```

### Тестирование параметров
```bash
# Простые параметры
REQUEST_URI="/users/123" php url-parameters-example.php

# Множественные параметры  
REQUEST_URI="/blog/technology/456" php url-parameters-example.php

# Глубокие параметры
REQUEST_URI="/users/1/posts/2/comments/3" php url-parameters-example.php

# Валидация параметров
REQUEST_URI="/validation/abc" php url-parameters-example.php
```

## 📖 Путь обучения

### Уровень 1: Новичок
1. **basic-example.php** - основы роутинга
2. **url-parameters-example.php** - работа с параметрами

### Уровень 2: Средний  
3. **response-types-example.php** - типы ответов
4. **rest-api-example.php** - REST API

### Уровень 3: Продвинутый
5. **error-handling-example.php** - обработка ошибок

## 💡 Ключевые концепции

### Маршруты
- `Route::create()` - маршруты с контроллерами
- `RouteAnonymousFunc::create()` - анонимные функции
- URL параметры `{id}`, `{name}`, `{category}`
- HTTP методы: GET, POST, PUT, DELETE

### Контроллеры
- Методы контроллеров принимают параметры URL
- Инжекция `Request` объекта
- Возврат `Response` объектов

### Response типы
- `Response::json()` - JSON ответы
- `Response::html()` - HTML страницы  
- `Response::redirect()` - редиректы
- `Response::create()` - кастомные ответы

### Обработка ошибок
- PHP исключения
- Кастомные Exception классы
- HTTP статус-коды
- Try-catch блоки

## 🔧 Особенности примеров

### ✅ Полнота
- Каждый пример самодостаточен
- Включены все необходимые классы
- Подробные комментарии

### ✅ Готовность к запуску
- Работают из CLI "из коробки"
- Не требуют дополнительной настройки
- Подробные инструкции по тестированию

### ✅ Прогрессивная сложность
- От простого к сложному
- Каждый уровень строится на предыдущем
- Четкое разделение по уровням сложности

### ✅ Практичность
- Реальные сценарии использования
- Best practices
- Готовые решения для продакшена

## 🛠️ Технические детали

### Требования
- PHP 8.0+
- Composer autoloader
- FaustVik Router library

### Структура файлов
```
examples/
├── README.md                    # Этот файл
├── basic-example.php           # Базовые возможности
├── url-parameters-example.php  # URL параметры  
├── response-types-example.php  # Типы ответов
├── rest-api-example.php        # REST API
└── error-handling-example.php  # Обработка ошибок
```

### PHPStan совместимость
Все примеры соответствуют PHPStan Level 5:
- Строгая типизация
- Правильные аннотации типов
- Отсутствие неиспользуемых переменных

## 📞 Поддержка

Если у вас возникли вопросы по примерам:

1. Прочитайте комментарии в коде
2. Попробуйте разные варианты тестирования
3. Изучите вывод каждого примера
4. Проверьте требования к PHP и зависимостям

Каждый пример содержит подробную справку по использованию и тестированию. 