# Документация Router v2.0-alpha

Центр документации проекта Router.

---

## 📚 Основные документы

### [ROUTER_ISSUES_2025.md](ROUTER_ISSUES_2025.md) - Актуальные проблемы ⭐
Основной документ с текущими проблемами и планом развития:
- Сводка по категориям
- Список актуальных проблем
- Решения и примеры кода
- План приоритизации
- **Обновляется по мере разработки**

### [CONCEPT.md](CONCEPT.md) - Концепция проекта
Философия и принципы проекта:
- Миссия и видение
- Целевая аудитория (новички и опытные разработчики)
- Философия: простота + мощность
- Анализ конкурентов

### [QUICK_START.md](QUICK_START.md) - Быстрый старт
QuickRouter - Hello World за 5 минут:
- Простой API для новичков
- Базовые примеры
- REST API примеры

---

## 🎯 С чего начать?

### Новый пользователь:
1. Читайте [QUICK_START.md](QUICK_START.md)
2. Смотрите примеры в `/examples`

### Разработчик проекта:
1. Читайте [ROUTER_ISSUES_2025.md](ROUTER_ISSUES_2025.md) - актуальные задачи
2. Выбирайте задачу из плана приоритизации
3. Создавайте PR

### Контрибьютор:
1. Читайте [CONCEPT.md](CONCEPT.md) - поймите философию
2. Читайте [ROUTER_ISSUES_2025.md](ROUTER_ISSUES_2025.md) - выберите задачу
3. Создайте GitHub Issue
4. Создайте PR с решением

---

## 📁 Структура документации

```
docs/
├── README.md                   # Этот файл - навигация
├── ROUTER_ISSUES_2025.md      # ⭐ Актуальные проблемы (главный документ)
├── CONCEPT.md                  # Концепция и философия
├── QUICK_START.md              # Быстрый старт с QuickRouter
├── DI_MIDDLEWARE_IMPLEMENTATION.md  # Техническая документация по DI
├── FUTURE_PLANS.md             # Планы на будущее
├── ISSUES_SUMMARY.md           # Старая сводка (устарела)
└── TECHNICAL_ISSUES.md         # Старый детальный анализ (устарел)
```

---

## 🗂️ Примеры кода

Все примеры находятся в `/examples`:
- `basic-example.php` - базовые возможности
- `rest-api-example.php` - REST API
- `middleware-di-example.php` - DI в middleware
- `global-middleware-example.php` - глобальные middleware
- `named-routes-advanced-example.php` - генерация URL с query и якорями
- И многое другое...

См. [examples/README.md](/examples/README.md) для полного списка.

---

## 📊 Текущий статус проекта

**Версия:** v2.0-alpha  
**Статус:** Активная разработка  

**Что работает:**
- ✅ Базовая маршрутизация
- ✅ Middleware (глобальные и на уровне маршрута)
- ✅ Dependency Injection
- ✅ Named Routes с генерацией URL
- ✅ Query параметры и якоря
- ✅ Кеширование
- ✅ Request/Response объекты
- ✅ Безопасность (JSON вместо serialize, защита от Path Traversal)

**Что нужно:**
- ❌ Больше тестов (цель: 80%+ покрытие)
- ❌ Оптимизация матчинга (Radix Tree)
- ❌ PSR-7/PSR-15 совместимость
- ❌ RateLimitMiddleware, CsrfMiddleware

См. [ROUTER_ISSUES_2025.md](ROUTER_ISSUES_2025.md) для деталей.

---

**Последнее обновление:** 8 октября 2025
