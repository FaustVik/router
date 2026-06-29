# PHP Router

Modern, lightweight PHP Router with middleware support, dependency injection, and caching.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

[🇷🇺 Русская версия](README.ru.md)

## ✨ Features

- 🚀 **Fast & Lightweight** - Optimized route matching with O(1) lookup for static routes
- 🎯 **Dynamic URL Parameters** - Support for `/users/{id}` patterns with constraints
- 🔌 **Middleware Support** - Built-in middleware stack with popular implementations
- 💉 **Dependency Injection** - Automatic dependency resolution for controllers
- 💾 **Route Caching** - 2-10x performance boost in production
- 🏷️ **Named Routes** - URL generation by route name
- 📦 **Route Groups** - Organize routes with prefixes and shared middleware
- 🎭 **Anonymous Functions** - Use closures as route handlers
- 🔒 **Security** - Built-in CSRF, CORS, Auth, and Rate Limiting middleware
- 📝 **PSR-Compatible** - Clean, modern PHP 8.1+ codebase

## 📋 Requirements

- PHP 8.1 or higher

## 📥 Installation

```bash
composer require faustvijk/router
```

## 🚀 Quick Start

### Option 1: QuickRouter (Recommended for Beginners)

```php
use FaustVik\Router\Router\QuickRouter;

$app = new QuickRouter();

// Simple route
$app->get('/', fn() => "Hello World!");

// Route with parameters
$app->get('/users/{id}', fn($id) => "User #$id");

// Route with controller
$app->get('/posts', [PostController::class, 'index']);

$app->run();
```

### Option 2: Full Router (Advanced Features)

```php
use FaustVik\Router\Router\Router;
use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RoutesCollection;

$routes = new RoutesCollection();

// Add routes
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

## 📖 Core Concepts

### Dynamic URL Parameters

```php
// Basic parameter
$app->get('/users/{id}', fn($id) => "User #$id");

// Multiple parameters
$app->get('/posts/{year}/{month}', fn($year, $month) => 
    "Archive: $year-$month"
);

// Optional parameters
$app->get('/posts/{year?}/{month?}', [PostController::class, 'archive']);

// With constraints
Route::create('/users/{id}', UserController::class, 'show')
    ->where('id', '\d+')  // Only digits
    ->where('slug', '[a-z0-9\-]+');  // Alphanumeric and dashes
```

### Middleware

```php
use FaustVik\Router\Middleware\AuthMiddleware;
use FaustVik\Router\Middleware\CorsMiddleware;
use FaustVik\Router\Middleware\LoggingMiddleware;

// Route-specific middleware
$route = Route::create('/admin/users', AdminController::class, 'index')
    ->middleware([AuthMiddleware::class, LoggingMiddleware::class]);

// Global middleware (applies to all routes)
$app->addMiddleware(CorsMiddleware::class)
    ->addMiddleware(LoggingMiddleware::class);

// Group middleware
$app->middleware([AuthMiddleware::class], function($app) {
    $app->get('/admin', [AdminController::class, 'index']);
    $app->get('/profile', [ProfileController::class, 'show']);
});
```

#### Built-in Middleware

- **AuthMiddleware** - Bearer token authentication
- **CorsMiddleware** - CORS headers and preflight requests
- **CsrfMiddleware** - CSRF token protection
- **RateLimitMiddleware** - Request rate limiting
- **LoggingMiddleware** - HTTP request logging

#### Custom Middleware

```php
use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Interfaces\Middleware\MiddlewareInterface;

class CustomMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Before route execution
        
        $response = $next($request);
        
        // After route execution
        
        return $response;
    }
}
```

### Route Caching

```php
use FaustVik\Router\Cache\FileCache;

$router = new Router();

// Enable caching (2-10x performance boost)
$router->enableCache();

// Custom cache configuration
$cache = new FileCache('cache/routes', 'app_');
$router->setCache($cache);
$router->getConfig()->setCacheTtl(3600); // 1 hour

// Clear cache
$router->clearRouteCache();
```

**Performance**: Caching can speed up request processing by 2-10x, especially with many routes.

### Named Routes & URL Generation

```php
// Define named routes
Route::create('/users/{id}', UserController::class, 'show')
    ->name('users.show');

Route::create('/posts/{year}/{slug}', PostController::class, 'show')
    ->name('posts.show');

// Generate URLs
$router->url('users.show', ['id' => 123]);
// => /users/123

$router->url('posts.show', [
    'year' => 2025,
    'slug' => 'my-post'
], ['ref' => 'twitter']);
// => /posts/2025/my-post?ref=twitter

// Check route existence
if ($router->has('users.show')) {
    $url = $router->url('users.show', ['id' => 123]);
}
```

### Route Groups

```php
// Prefix groups
$app->prefix('/api', function($app) {
    $app->get('/users', [UserController::class, 'index']);
    $app->get('/posts', [PostController::class, 'index']);
});
// Creates: /api/users and /api/posts

// With RoutesCollection
$routes->prefix('/admin')->group(function($group) {
    $group->get('/users', UserController::class, 'index')
        ->name('admin.users.index');
    
    $group->get('/settings', SettingsController::class, 'index')
        ->name('admin.settings');
});
```

### Dependency Injection

```php
// Enable DI
$router->enableDI();

// Configure bindings
$router->enableDI(function($container) {
    $container->singleton(DatabaseInterface::class, MySQLDatabase::class);
    $container->bind(LoggerInterface::class, FileLogger::class);
});

// Controllers with dependencies
class UserController
{
    public function __construct(
        private DatabaseInterface $db,
        private LoggerInterface $logger
    ) {}
    
    public function show($id): Response
    {
        $user = $this->db->find('users', $id);
        $this->logger->info("User viewed: $id");
        
        return Response::json($user);
    }
}
```

### Request & Response

```php
use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;

class UserController
{
    public function show(Request $request, $id): Response
    {
        // Get query parameters
        $page = $request->getQueryParam('page', 1);
        
        // Get JSON body
        $data = $request->input('name');
        
        // Get headers
        $token = $request->getHeader('Authorization');
        
        // Return JSON response
        return Response::json([
            'user' => ['id' => $id, 'name' => 'John'],
            'page' => $page
        ]);
    }
    
    public function create(Request $request): Response
    {
        // Handle file upload
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            move_uploaded_file($file['tmp_name'], 'uploads/' . $file['name']);
        }
        
        // Redirect
        return Response::redirect('/users');
    }
}
```

## 📚 Examples

Comprehensive examples demonstrating router capabilities from basic to advanced:

### 🟢 Beginner Level
- **[quick-example.php](examples/quick-example.php)** - QuickRouter introduction (5 minutes to start)
- **[basic-example.php](examples/basic-example.php)** - Full router with controllers
- **[url-parameters-example.php](examples/url-parameters-example.php)** - URL parameter handling

### 🟡 Intermediate Level  
- **[middleware-example.php](examples/middleware-example.php)** - Middleware basics
- **[response-types-example.php](examples/response-types-example.php)** - JSON, HTML, redirects
- **[rest-api-example.php](examples/rest-api-example.php)** - Full REST API with CRUD
- **[named-routes-example.php](examples/named-routes-example.php)** - URL generation

### 🟠 Advanced Level
- **[di-basic-example.php](examples/di-basic-example.php)** - Dependency injection
- **[cache-example.php](examples/cache-example.php)** - Route caching
- **[error-handling-example.php](examples/error-handling-example.php)** - Error handling
- **[csrf-protection-example.php](examples/csrf-protection-example.php)** - CSRF protection

### Quick Test
```bash
cd examples

# Test basic routing
php quick-example.php

# Test with specific route
REQUEST_URI="/users/123" php basic-example.php

# Test REST API
REQUEST_URI="/api/users" php rest-api-example.php
```

See **[examples/README.md](examples/README.md)** for full documentation.

## 🏗️ Architecture

### Components

- **Router** - Main router class with route matching and execution
- **Route** - Route definition with controller class
- **RouteAnonymousFunc** - Route with closure handler
- **RoutesCollection** - Route registry and management
- **Config** - Router component configuration
- **MiddlewareStack** - Chain of Responsibility pattern implementation

### Matching Strategies

- **Matching** - Basic route matching
- **OptimizedMatching** - Indexed matching with O(1) static route lookup
- **CachedMatching** - Caching wrapper for matching results

## 🧪 Testing

```bash
# Run tests
composer test

# Run with coverage
composer test-coverage

# Static analysis
composer phpstan
```

## 📖 Documentation

- [Quick Start Guide](docs/QUICK_START.md)
- [Concept & Architecture](docs/CONCEPT.md)
- [DI & Middleware Implementation](docs/DI_MIDDLEWARE_IMPLEMENTATION.md)
- [Future Plans](docs/FUTURE_PLANS.md)

## 🤝 Contributing

Contributions are welcome! This library is designed to be accessible for both beginners and experienced developers. We're not trying to replace Symfony or Yii2 routers, but provide a lightweight, easy-to-understand alternative.

## 📄 License

MIT License. See [LICENSE](LICENSE) file for details.

## 🌟 Acknowledgments

Created with ❤️ for the PHP community.
