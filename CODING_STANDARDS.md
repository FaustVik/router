# Стандарты кодирования

Этот документ описывает стандарты кодирования для проекта FaustVik Router.

## Инструменты

### PHP CodeSniffer
Проект использует PHP CodeSniffer для проверки соответствия кода стандартам.

**Проверка кода:**
```bash
composer run cs-check
# или
vendor/bin/phpcs
```

**Автоматическое исправление:**
```bash
composer run cs-fix
# или
vendor/bin/phpcbf
```

### Основные стандарты

- **PSR-12** - основной стандарт кодирования
- **PHPStan Level 5** - статический анализ кода
- **EditorConfig** - настройки редактора

## Основные правила

### 1. Отступы и форматирование
- Используйте 4 пробела для отступов
- Максимальная длина строки: 120 символов
- Unix-style переносы строк (LF)
- Файлы должны заканчиваться пустой строкой

### 2. Комментарии и документация
- Все публичные методы должны иметь PHPDoc комментарии
- Комментарии пишем на русском языке
- Используйте полные namespace в @throws аннотациях

**Пример правильного комментария:**
```php
/**
 * Получение пользователя по ID
 *
 * @param int $id Идентификатор пользователя
 * @return User|null Пользователь или null
 * @throws \FaustVik\Router\exceptions\ValidationException Если ID невалидный
 */
public function getUserById(int $id): ?User
{
    // реализация
}
```

### 3. Именование
- Классы: `PascalCase` (например, `UserController`)
- Методы и переменные: `camelCase` (например, `getUserById`)
- Константы: `UPPER_CASE` (например, `DEFAULT_TIMEOUT`)

### 4. Namespace и импорты
- Используйте полные namespace для всех классов
- Группируйте use-выражения по типу (core, vendor, local)
- Удаляйте неиспользуемые импорты

### 5. Исключения для примеров
Для файлов в директории `examples/` действуют более мягкие правила:
- Разрешено несколько классов в одном файле
- Не требуется namespace
- Не требуются PHPDoc комментарии

## Настройка IDE

### PhpStorm
1. Установите плагин EditorConfig
2. Настройте Code Style: `Settings → Code Style → PHP`
3. Импортируйте настройки из `phpcs.xml`

### VS Code
1. Установите расширения:
   - EditorConfig for VS Code
   - PHP Intelephense
   - PHP CodeSniffer
2. Настройте в `settings.json`:
```json
{
    "phpcs.standard": "phpcs.xml",
    "php.validate.enable": true,
    "editor.formatOnSave": true
}
```

## Проверка перед коммитом

Перед каждым коммитом обязательно выполняйте:

```bash
# Проверка стандартов кодирования
composer run cs-check

# Автоматическое исправление
composer run cs-fix

# Статический анализ
composer run phpstan
```

## Continuous Integration

В CI/CD пайплайне настроены автоматические проверки:
- PHP CodeSniffer для проверки стандартов
- PHPStan для статического анализа
- Тесты для функциональности

## Исключения и настройки

Конфигурация находится в файле `phpcs.xml`. Основные настройки:

- **Исключения для примеров**: файлы в `examples/` имеют более мягкие требования
- **Длина строки**: 120 символов для кода, 140 абсолютный максимум
- **Кодировка**: UTF-8
- **Отступы**: 4 пробела

## Дополнительные правила

### Массивы
```php
// Правильно
$array = [
    'key1' => 'value1',
    'key2' => 'value2',
];

// Неправильно
$array = array('key1' => 'value1', 'key2' => 'value2');
```

### Типизация
```php
// Правильно
public function process(string $data): bool
{
    return true;
}

// Неправильно
public function process($data)
{
    return true;
}
```

### Обработка ошибок
```php
// Правильно
try {
    $result = $this->processData($data);
} catch (ValidationException $e) {
    $this->logger->error('Validation failed: ' . $e->getMessage());
    throw $e;
}
```

## Обновления

Этот документ может обновляться по мере развития проекта. Следите за изменениями в репозитории.

## Контакты

При вопросах по стандартам кодирования обращайтесь к мейнтейнерам проекта. 