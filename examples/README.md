# FaustVik Router Examples

[🇷🇺 Русская версия](README.ru.md)

## Examples Overview

### For Beginners
- **`quick-example.php`** - QuickRouter introduction - Hello World in 5 minutes
- **`basic-example.php`** - Router introduction, basic routes, controllers, URL parameters
- **`simple-groups-example.php`** - Simple route grouping demonstration

### Intermediate Level
- **`url-parameters-example.php`** - Advanced URL parameters, nested parameters
- **`response-types-example.php`** - Different response types: JSON, HTML, redirects, headers
- **`groups-example.php`** - Complex route grouping with middleware and nesting
- **`middleware-example.php`** - Middleware basics

### Advanced Level
- **`rest-api-example.php`** - Full REST API with CRUD operations
- **`named-routes-example.php`** - Named Routes and URL generation
- **`named-routes-advanced-example.php`** - ✨ **NEW!** Advanced URL generation with query parameters and fragments
- **`post-data-example.php`** - ✨ **NEW!** Working with POST/PUT/PATCH data, JSON body, file uploads
- **`cookies-and-security-example.php`** - ✨ **NEW!** Cookies, IP detection, HTTPS check, Method Override
- **`middleware-di-example.php`** - ✨ **NEW!** DI in Middleware - working with middleware dependencies
- **`global-middleware-example.php`** - ✨ **NEW!** Global middleware for all routes
- **`error-handling-example.php`** - Error handling, exceptions, HTTP codes
- **`di-basic-example.php`** - Dependency injection basics
- **`cache-example.php`** - Basic route caching
- **`cache-production-example.php`** - Caching for production environment
- **`csrf-protection-example.php`** - CSRF token protection
- **`rate-limit-example.php`** - Request rate limiting

## New Features in v.2.0-alpha

### 🏷️ Named Routes and URL Generation

#### Basic URL Generation
```php
// Create named route
Route::create('/users/{id}', UserController::class, 'show')
    ->name('users.show')
    ->where('id', '\d+');

// Generate URL
$router->url('users.show', ['id' => 123]); // => /users/123
```

#### Query Parameters (new!)
```php
$router->url('users.index', [], ['page' => 2, 'sort' => 'name']);
// => /users?page=2&sort=name
```

#### Anchors/Fragments (new!)
```php
$router->url('posts.show', ['id' => 456], [], 'comments');
// => /posts/456#comments
```

#### Full Example (new!)
```php
$router->url(
    name: 'posts.show',
    params: ['id' => 456],
    query: ['ref' => 'home'],
    fragment: 'comments'
);
// => /posts/456?ref=home#comments
```

#### Practical Usage
```php
// Pagination
$prevUrl = $router->url('products.index', [], ['page' => $page - 1]);
$nextUrl = $router->url('products.index', [], ['page' => $page + 1]);

// Filtering
$filterUrl = $router->url('products.index', [], [
    'category' => 'electronics',
    'price_min' => 100,
    'price_max' => 500
]);

// Documentation navigation with anchors
$docUrl = $router->url('docs.show', ['section' => 'api'], [], 'authentication');
```

### 🚀 Route Caching

Caching significantly speeds up request processing, especially with many routes.

#### Basic Caching
```php
$router = new Router();

// Enable caching
$router->enableCache();

// Configure cache
$cache = new FileCache('cache/routes', 'app_');
$router->setCache($cache);

// Configure TTL
$router->getConfig()->setCacheTtl(3600); // 1 hour
```

#### Cache Management
```php
// Check status
if ($router->isCacheEnabled()) {
    echo "Cache enabled";
}

// Clear cache
$router->clearRouteCache();

// Disable cache
$router->disableCache();
```

#### Performance
- **Without cache**: ~5-10 ms per request
- **With cache**: ~0.5-1 ms per request (initial caching)
- **From cache**: ~0.1-0.3 ms per request

### 🎯 Route Grouping

Grouping allows combining routes with common properties:

#### Simple Grouping with Prefix
```php
$routes->prefix('/api')->group(function($group) {
    $group->get('/users', UserController::class, 'index');     // /api/users
    $group->post('/users', UserController::class, 'create');   // /api/users
    $group->get('/users/{id}', UserController::class, 'show'); // /api/users/{id}
});
```

#### Grouping with Middleware
```php
$routes->middleware([AuthMiddleware::class])
    ->prefix('/admin')
    ->group(function($group) {
        $group->get('/dashboard', AdminController::class, 'dashboard');
        $group->get('/users', AdminController::class, 'users');
    });
```

#### Nested Grouping
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

#### Anonymous Functions in Groups
```php
$routes->prefix('/blog')->group(function($group) {
    $group->getFunc('/rss', function(): Response {
        return Response::create('RSS Feed')->withHeader('Content-Type', 'application/xml');
    });
});
```

#### Grouping Methods

**HTTP methods for classes:**
- `get(route, class, action)` - GET requests
- `post(route, class, action)` - POST requests
- `put(route, class, action)` - PUT requests
- `delete(route, class, action)` - DELETE requests
- `patch(route, class, action)` - PATCH requests
- `any(route, class, action)` - Any HTTP methods
- `match([methods], route, class, action)` - Specified methods

**HTTP methods for anonymous functions:**
- `getFunc(route, function)` - GET with function
- `postFunc(route, function)` - POST with function
- `putFunc(route, function)` - PUT with function
- `deleteFunc(route, function)` - DELETE with function
- `anyFunc(route, function)` - Any methods with function

**Group management:**
- `prefix(string)` - Add prefix to routes
- `middleware(array)` - Apply middleware to group
- `group(callable)` - Create nested group

## Quick Start

### 1. Simplest Example
```bash
REQUEST_METHOD="GET" REQUEST_URI="/" php basic-example.php
```

### 2. Testing Groups
```bash
REQUEST_METHOD="GET" REQUEST_URI="/api/v1/users" php simple-groups-example.php
```

### 3. REST API
```bash
REQUEST_METHOD="GET" REQUEST_URI="/api/users" php rest-api-example.php
```

## Concepts

### 🔄 URL Parameters
Routes support parameters in curly braces: `/users/{id}`, `/posts/{postId}/comments/{commentId}`

### 🛡️ Middleware
Middleware processes requests before/after controllers: authentication, logging, CORS

#### Global Middleware (new in v2.0-alpha!)
Applied to all routes automatically:
```php
// Router
$router->addGlobalMiddleware(CorsMiddleware::class)
       ->addGlobalMiddleware(LoggingMiddleware::class);

// QuickRouter
$app = new QuickRouter();
$app->addMiddleware(CorsMiddleware::class)
    ->addMiddleware(LoggingMiddleware::class);

// Now all routes will pass through these middleware
```

#### Route Middleware
Applied to specific route:
```php
$route->middleware([AuthMiddleware::class]);
```

**Execution order**: global → route middleware → controller

### 📊 Request/Response
- `Request` object contains request data, route parameters, headers
- `Response` object allows creating JSON, HTML, redirects with headers

### ⚡ Performance
- PHPStan Level 5 - strict typing
- Automatic dependency injection
- Compatible with CLI and web server

## Testing

All examples can be tested via CLI:

```bash
cd examples/

# QuickRouter introduction
php quick-example.php

# Basic example
REQUEST_METHOD="GET" REQUEST_URI="/" php basic-example.php

# Route grouping
REQUEST_METHOD="GET" REQUEST_URI="/api/v2/users/123" php simple-groups-example.php

# URL parameters
REQUEST_METHOD="GET" REQUEST_URI="/users/123/posts/456/comments/789" php url-parameters-example.php

# JSON responses
REQUEST_METHOD="GET" REQUEST_URI="/api/users" php response-types-example.php

# REST API
REQUEST_METHOD="POST" REQUEST_URI="/api/users" php rest-api-example.php

# Error handling
REQUEST_METHOD="GET" REQUEST_URI="/errors/validation" php error-handling-example.php

# Caching
REQUEST_METHOD="GET" REQUEST_URI="/user/123/profile/settings" php cache-example.php
REQUEST_METHOD="GET" REQUEST_URI="/api/users/123" php cache-production-example.php

# Global middleware
php global-middleware-example.php

# Named Routes with query parameters and fragments
php named-routes-advanced-example.php

# CSRF protection
php csrf-protection-example.php

# Rate limiting
php rate-limit-example.php
```

## Learning Path

1. **Start with `quick-example.php`** - 5-minute introduction to QuickRouter
2. **Then `basic-example.php`** - learn the basics
3. **Move to `simple-groups-example.php`** - master grouping
4. **Study `url-parameters-example.php`** - route parameters
5. **Try `response-types-example.php`** - different response types
6. **Create API with `rest-api-example.php`** - CRUD operations
7. **Master `error-handling-example.php`** - error handling
8. **Study `groups-example.php`** - complex grouping
9. **Learn `cache-example.php`** - basic caching
10. **Master `cache-production-example.php`** - production caching
11. **Explore `di-basic-example.php`** - dependency injection
12. **Try `csrf-protection-example.php`** - CSRF protection
13. **Study `rate-limit-example.php`** - rate limiting

Each example contains detailed comments and is ready to run! 🚀

