# Router

PHP Router

## Example

```php
$collections = new RoutesCollection();

$fd = RouteAnonymousFunc::create('/anonym', static function (){
    echo "Anonymous Func";
}, ['POST']);

$collections->set(
    Route::create('/', TestController::class, 'actionIndex',[], ['POST'], '/aliasIndex'),
    $fd,
);

$config = new Config();
$config->setRunner(new Runner());

$router = new Router();
$router->setConfig($config);
$router->setCollection($collections);
$router->run();
```

### Route class
Route for class methods

- **Route** routing from uri, for example /test
- **Class** Controller class
- **Action** action (method) controller class
- **Arg** arguments for constructor controller class (optional)
- **Methods** list allowed http methods (POST, GET, PUT etc..)
- **Alias** alias for route (instead of **/test** **/testalias**) (optional)

Example:
```php
Route::create('/', TestController::class, 'actionIndex',[], ['POST'], '/aliasIndex'),
```

### Route Anonymous func
Route for anonymous functions

- **Route** routing from uri, for example /test
- **Func** anonymous function for route
- **Methods** list allowed http methods (POST, GET, PUT etc..)
- **Alias** alias for route (instead of **/test** **/testalias**) (optional)

Example:
```php
RouteAnonymousFunc::create('/anonym', static function (){
    echo "Anonymous Func";
}, ['POST']);
```

### Router

Router class that parses the uri and run the action

### Runner
the component is responsible for launching the action for the route
Class for run action (controller class or anonymous function)

Runs a class method or an anonymous function

### Matcher
Compares the uri against the list of the route and tries to find a match or throws an exception

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
