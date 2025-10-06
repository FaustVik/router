# Router

PHP Router с поддержкой параметров в URL

## Example

```php
$collections = new RoutesCollection();

// Обычный маршрут
$collections->set(
    Route::create('/', TestController::class, 'actionIndex', [], ['GET']),
);

// Маршрут с параметрами
$collections->set(
    Route::create('/user/{id}', UserController::class, 'show', [], ['GET']),
    Route::create('/user/{id}/edit', UserController::class, 'edit', [], ['GET', 'POST']),
    Route::create('/category/{category}/post/{id}', PostController::class, 'show', [], ['GET']),
);

// Анонимная функция с параметрами
$fd = RouteAnonymousFunc::create('/hello/{name}', static function ($name) {
    echo "Hello, " . $name;
}, ['GET']);

$collections->set($fd);

$config = new Config();
$config->setRunner(new Runner());

$router = new Router();
$router->setConfig($config);
$router->setCollection($collections);
$router->run();
```

## Middleware поддержка

Роутер поддерживает middleware для обработки запросов. Middleware выполняется в порядке добавления.

### Основные классы для middleware

- **Request** - объект HTTP запроса
- **Response** - объект HTTP ответа  
- **MiddlewareInterface** - интерфейс для создания middleware
- **MiddlewareStack** - управление стеком middleware

### Примеры использования

```php
use FaustVik\Router\Middleware\AuthMiddleware;
use FaustVik\Router\Middleware\LoggingMiddleware;
use FaustVik\Router\Middleware\CorsMiddleware;

// Маршрут с middleware
$route = Route::create('/admin/users', AdminController::class, 'index', [], ['GET'])
    ->middleware([
        AuthMiddleware::class,
        LoggingMiddleware::class
    ]);

// Анонимная функция с middleware
$route = RouteAnonymousFunc::create('/api/data', function() {
    return Response::json(['data' => 'Hello World']);
}, ['GET'])
    ->middleware([
        CorsMiddleware::class,
        LoggingMiddleware::class
    ]);
```

### Встроенные middleware

#### AuthMiddleware
Проверяет Authorization заголовок и устанавливает атрибуты пользователя.

```php
$route->middleware([AuthMiddleware::class]);

// В контроллере можно получить данные пользователя
function show(Request $request) {
    $userId = $request->getAttribute('user_id');
    $isAuthenticated = $request->getAttribute('authenticated');
}
```

#### LoggingMiddleware
Логирует HTTP запросы с информацией о времени выполнения.

```php
$route->middleware([new LoggingMiddleware('custom.log')]);
```

#### CorsMiddleware
Обрабатывает CORS заголовки для API.

```php
$corsMiddleware = new CorsMiddleware(
    allowedOrigins: ['https://example.com'],
    allowedMethods: ['GET', 'POST'],
    allowedHeaders: ['Content-Type', 'Authorization'],
    allowCredentials: true
);

$route->middleware([$corsMiddleware]);
```

### Создание собственного middleware

```php
use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\interfaces\Middleware\MiddlewareInterface;

class CustomMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Логика до выполнения действия
        
        $response = $next($request);
        
        // Логика после выполнения действия
        
        return $response;
    }
}
```

## Кеширование

Роутер поддерживает кеширование результатов матчинга для повышения производительности.

### Включение кеширования

```php
use FaustVik\Router\Cache\FileCache;

$router = new Router();

// Включаем кеширование
$router->enableCache();

// Настраиваем кеш (опционально)
$cache = new FileCache('cache/routes', 'app_');
$router->setCache($cache);

// Настраиваем TTL через конфигурацию
$config = $router->getConfig();
$config->setCacheTtl(3600); // 1 час
```

### Управление кешем

```php
// Проверяем статус кеша
if ($router->isCacheEnabled()) {
    echo "Кеш включен";
}

// Получаем кеш-драйвер
$cache = $router->getCache();

// Очищаем кеш
$router->clearRouteCache();

// Отключаем кеш
$router->disableCache();
```

### Конфигурация кеша

```php
// Создание кеша с настройками
$cache = new FileCache(
    cacheDir: 'cache/routes',  // Папка для кеша
    prefix: 'my_app_'          // Префикс для файлов
);

// Настройка TTL
$config = $router->getConfig();
$config->setCacheTtl(7200); // 2 часа
```

### Примеры использования

- **Разработка**: Кеширование обычно отключено для мгновенного отображения изменений
- **Продакшн**: Кеширование включено для максимальной производительности

```php
// Настройка для разных окружений
$isProduction = (getenv('APP_ENV') === 'production');

if ($isProduction) {
    $router->enableCache();
    $router->getConfig()->setCacheTtl(3600);
}
```

### Производительность

Кеширование может ускорить обработку запросов в 2-10 раз, особенно при большом количестве маршрутов.

## Статический анализ кода

Проект использует PHPStan для статического анализа кода:

```bash
# Анализ кода
composer phpstan

# Создание baseline (если нужно игнорировать существующие ошибки)
composer phpstan-baseline
```

## Параметры в URL

Роутер поддерживает параметры в URL в формате `{parameter_name}`. Примеры:

- `/user/{id}` - будет соответствовать `/user/123`, `/user/456` и т.д.
- `/category/{category}/post/{id}` - будет соответствовать `/category/tech/post/123`
- `/hello/{name}` - будет соответствовать `/hello/john`

### Использование в контроллерах

```php
class UserController 
{
    public function show($id) 
    {
        echo "User ID: " . $id;
    }
    
    public function edit($id) 
    {
        echo "Edit user ID: " . $id;
    }
}

class PostController 
{
    public function show($category, $id) 
    {
        echo "Category: " . $category . ", Post ID: " . $id;
    }
}
```

### Приоритет параметров

1. **Параметры из URL** (например, `/user/{id}`) имеют высший приоритет
2. **Query параметры** (например, `?name=value`) имеют меньший приоритет

Если у вас есть маршрут `/user/{id}` и запрос `/user/123?id=456`, то в контроллер будет передан `id = 123`.

### Route class
Route for class methods

- **Route** routing from uri, for example `/test` or `/user/{id}`
- **Class** Controller class
- **Action** action (method) controller class
- **Arg** arguments for constructor controller class (optional)
- **Methods** list allowed http methods (POST, GET, PUT etc..)
- **Alias** alias for route (instead of **/test** **/testalias**) (optional)

Example:
```php
Route::create('/user/{id}', UserController::class, 'show', [], ['GET']),
```

### Route Anonymous func
Route for anonymous functions

- **Route** routing from uri, for example `/test` or `/hello/{name}`
- **Func** anonymous function for route
- **Methods** list allowed http methods (POST, GET, PUT etc..)
- **Alias** alias for route (instead of **/test** **/testalias**) (optional)

Example:
```php
RouteAnonymousFunc::create('/hello/{name}', static function ($name) {
    echo "Hello, " . $name;
}, ['GET']);
```

### Router

Router class that parses the uri and run the action

### Runner
the component is responsible for launching the action for the route
Class for run action (controller class or anonymous function)

Runs a class method or an anonymous function

### Matcher
Compares the uri against the list of the route and tries to find a match or throws an exception.
Supports URL parameters in format `{parameter_name}`.

### CheckerHttpMethod

Сheck for permission for the found route allowed HTTP methods.

### Config

Config router

Methods:
- `setRunner()`
- `setCheckerHttpMethod()`
- `setMatcher()`

You can add (implement the interfaces) custom components for the router (by default, components from the directory `FaustVik\Router\Router\Component` are used):

and set to Config:
Example:
```php
$config = new Config();
$config->setRunner(new RunnerSmp());

$router = new Router();
$router->setConfig($config);
```

## Examples

Comprehensive examples demonstrating router capabilities from basic to advanced:

### 🟢 Beginner Level
- **[basic-example.php](examples/basic-example.php)** - Router introduction with controllers and URL parameters
- **[url-parameters-example.php](examples/url-parameters-example.php)** - Advanced URL parameter handling

### 🟡 Intermediate Level  
- **[response-types-example.php](examples/response-types-example.php)** - JSON, HTML, redirects, custom headers, XML, CORS
- **[rest-api-example.php](examples/rest-api-example.php)** - Full REST API with CRUD operations and relationships

### 🟠 Advanced Level
- **[error-handling-example.php](examples/error-handling-example.php)** - Comprehensive error handling and exception management

### Quick Start
```bash
cd examples
php basic-example.php
REQUEST_URI="/users/123" php basic-example.php
REQUEST_URI="/api" php rest-api-example.php
```

See **[examples/README.md](examples/README.md)** for detailed documentation, testing commands, and learning path.

## License

MIT
