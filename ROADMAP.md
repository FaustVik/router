# ROADMAP

> Планы развития faustvik/router

**Текущая версия:** v2.0-alpha  
**Обновлено:** июнь 2026

---

## Статус компонентов

| Компонент | Статус | Тесты |
|-----------|--------|-------|
| Маршрутизация (Route, Matching) | ✅ Готово | ✅ |
| QuickRouter (простой API) | ✅ Готово | ✅ |
| Middleware система | ✅ Готово | ✅ |
| DI контейнер + адаптеры | ✅ Готово | ✅ |
| Cache (File, CachedMatching) | ✅ Готово | ✅ |
| Named Routes + URL Generation | ✅ Готово | ✅ |
| Request/Response/Cookie | ✅ Готово | ✅ |
| Встроенные middleware (Auth, Cors, CSRF, RateLimit, Logging) | ✅ Готово | ✅ |
| Тесты | 415 тестов, 1028 assertions | |

---

## Приоритет 1 — PSR-7/PSR-15 совместимость

**Сложность:** Высокая | **Время:** 2-3 недели

Кастомные Request/Response не совместимы с PSR экосистемой (Guzzle, Slim и др.).

**Решение:** Адаптеры (Wrapper Pattern):
- `src/Http/Psr7/ServerRequestAdapter.php` — Request → PSR-7
- `src/Http/Psr7/ResponseAdapter.php` — Response → PSR-7
- `src/Middleware/Psr15MiddlewareAdapter.php` — PSR-15 middleware

---

## Приоритет 2 — Resource Routes

**Сложность:** Низкая | **Время:** 1-2 дня

Автоматическое создание REST маршрутов:
```php
$routes->resource('/posts', PostController::class);
// GET /posts → index, POST /posts → store, GET /posts/{id} → show, ...
```

---

## Приоритет 3 — Rate Limiter с Redis

**Сложность:** Средняя | **Время:** 3-5 дней

Текущий RateLimitMiddleware работает только с FileCache. Нужна поддержка Redis для production.

---

## Приоритет 4 — LoggingMiddleware + PSR-3

**Сложность:** Низкая | **Время:** 1-2 дня

Интеграция с PSR-3 LoggerInterface вместо file_put_contents.

---

## Приоритет 5 — Nice-to-have

| Фича | Сложность | Время |
|------|-----------|-------|
| Auto Type Casting (`{id:int}`) | Низкая | 1-2 дня |
| Route Model Binding | Средняя | 1 неделя |
| Events System | Средняя | 3-5 дней |
| API Versioning | Низкая | 1-2 дня |
| OpenAPI генерация | Средняя | 1 неделя |

---

## Что НЕ будет реализовано

- ORM / Database — не задача роутера
- Template Engine — используйте Twig/Blade
- Авторизация — через middleware
- Session Management — встроенные PHP сессии
- Database Migrations — Phinx, Doctrine

---

## Философия

Router — библиотека для маршрутизации, не фреймворк. Простота превыше всего. Сложные фичи опциональны.
