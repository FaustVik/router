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

## License

MIT
