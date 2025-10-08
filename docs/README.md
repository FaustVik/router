# Router v2.0-alpha Documentation

Documentation center for the Router project.

[🇷🇺 Русская версия](README.ru.md)

---

## 📚 Main Documents

### [ROUTER_ISSUES_2025.md](ROUTER_ISSUES_2025.md) - Current Issues ⭐
Main document with current issues and development plan:
- Summary by categories
- List of current issues
- Solutions and code examples
- Prioritization plan
- **Updated as development progresses**

### [CONCEPT.md](CONCEPT.md) - Project Concept
Project philosophy and principles:
- Mission and vision
- Target audience (beginners and experienced developers)
- Philosophy: simplicity + power
- Competitor analysis

### [QUICK_START.md](QUICK_START.md) - Quick Start
QuickRouter - Hello World in 5 minutes:
- Simple API for beginners
- Basic examples
- REST API examples

---

## 🎯 Where to Start?

### New User:
1. Read [QUICK_START.md](QUICK_START.md)
2. Check examples in `/examples`

### Project Developer:
1. Read [ROUTER_ISSUES_2025.md](ROUTER_ISSUES_2025.md) - current tasks
2. Choose a task from prioritization plan
3. Create a PR

### Contributor:
1. Read [CONCEPT.md](CONCEPT.md) - understand the philosophy
2. Read [ROUTER_ISSUES_2025.md](ROUTER_ISSUES_2025.md) - choose a task
3. Create a GitHub Issue
4. Create a PR with solution

---

## 📁 Documentation Structure

```
docs/
├── README.md                   # This file - navigation
├── ROUTER_ISSUES_2025.md      # ⭐ Current issues (main document)
├── CONCEPT.md                  # Concept and philosophy
├── QUICK_START.md              # Quick start with QuickRouter
├── DI_MIDDLEWARE_IMPLEMENTATION.md  # Technical DI documentation
├── FUTURE_PLANS.md             # Future plans
└── *.ru.md                     # Russian versions
```

---

## 🗂️ Code Examples

All examples are in `/examples`:
- `quick-example.php` - QuickRouter introduction
- `basic-example.php` - basic features
- `rest-api-example.php` - REST API
- `middleware-di-example.php` - DI in middleware
- `global-middleware-example.php` - global middleware
- `named-routes-advanced-example.php` - URL generation with query and fragments
- And much more...

See [examples/README.md](/examples/README.md) for full list.

---

## 📊 Current Project Status

**Version:** v2.0-alpha  
**Status:** Active development  

**What works:**
- ✅ Basic routing
- ✅ Middleware (global and route-level)
- ✅ Dependency Injection
- ✅ Named Routes with URL generation
- ✅ Query parameters and fragments
- ✅ Caching
- ✅ Request/Response objects
- ✅ Security (JSON instead of serialize, Path Traversal protection)

**What's needed:**
- ⏳ More tests (goal: 80%+ coverage)
- ⏳ Matching optimization (Radix Tree)
- ⏳ PSR-7/PSR-15 compatibility
- ✅ RateLimitMiddleware, CsrfMiddleware (implemented)

See [ROUTER_ISSUES_2025.md](ROUTER_ISSUES_2025.md) for details.

---

**Last updated:** October 8, 2025
