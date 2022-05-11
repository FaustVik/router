# Router

PHP Router

## Example

```php
$collections = new \FaustVik\Router\Route\RoutesCollection();

$fd = RouteAnonymousFunc::create('/anonym', static function (){
    echo "AnonymousFunc";
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
- **Route** routing from uri, for example /test
- **Class** Controller class
- **Action** action (method) controller class
- **Arg** arguments for constructor controller class (optional)
- **Methods** list allowed http methods (POST, GET, PUT etc..)
- **Alias** alias for route (instead of **/test** **/testalias**) (optional)

### Route Anonymous func
- **Route** routing from uri, for example /test
- **Func** anonymous function for route
- **Methods** list allowed http methods (POST, GET, PUT etc..)
- **Alias** alias for route (instead of **/test** **/testalias**) (optional)

### Router

Router class that parses the uri and run the action

### RunnerAction

Class for run action (controller class or anonymous function)

### Router Config

Config router

- you can make your config implement the interface `ConfigInterface`

- you can make your runner implement the interface `RunnerInterface`:
```php 
class RunnerSmp implements \FaustVik\Router\interfaces\RunnerInterface
```

and set to Config:

```php
$config = new Config();
$config->setRunner(new RunnerSmp());

$router = new Router();
$router->setConfig($config);
```

