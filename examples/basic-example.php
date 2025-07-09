<?php

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RouteAnonymousFunc;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;

// Простые контроллеры
class HomeController 
{
    public function index()
    {
        echo "Welcome to the Home Page!\n";
        echo "This is a simple router example.\n";
    }
    
    public function about()
    {
        echo "About Page\n";
        echo "Learn more about our application.\n";
    }
}

class UserController 
{
    public function list()
    {
        echo "User List:\n";
        echo "- John Doe (ID: 1)\n";
        echo "- Jane Smith (ID: 2)\n";
        echo "- Bob Johnson (ID: 3)\n";
    }
    
    public function show($id)
    {
        echo "User Profile\n";
        echo "User ID: $id\n";
        echo "Name: John Doe\n";
        echo "Email: john.doe@example.com\n";
    }
    
    public function create()
    {
        echo "Create User Form\n";
        echo "Please fill out the form to create a new user.\n";
    }
}

// Создаем коллекцию маршрутов
$routes = new RoutesCollection();

// === Простые маршруты с контроллерами ===
$routes->set(Route::create('/', HomeController::class, 'index', [], ['GET']));
$routes->set(Route::create('/about', HomeController::class, 'about', [], ['GET']));

// === Маршруты с URL параметрами ===
$routes->set(Route::create('/users', UserController::class, 'list', [], ['GET']));
$routes->set(Route::create('/users/create', UserController::class, 'create', [], ['GET']));
$routes->set(Route::create('/users/{id}', UserController::class, 'show', [], ['GET']));

// === Анонимные функции ===
$routes->set(RouteAnonymousFunc::create('/hello', static function () {
    echo "Hello, World!\n";
    echo "This is an anonymous function route.\n";
}, ['GET']));

$routes->set(RouteAnonymousFunc::create('/hello/{name}', static function ($name) {
    echo "Hello, $name!\n";
    echo "Welcome to our router example.\n";
}, ['GET']));

$routes->set(RouteAnonymousFunc::create('/greet/{greeting}/{name}', static function ($greeting, $name) {
    echo "$greeting, $name!\n";
    echo "Custom greeting example.\n";
}, ['GET']));

// === Маршрут с несколькими HTTP методами ===
$routes->set(RouteAnonymousFunc::create('/contact', static function () {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    echo "Contact Form\n";
    echo "HTTP Method: $method\n";
    if ($method === 'POST') {
        echo "Processing form submission...\n";
    } else {
        echo "Display contact form.\n";
    }
}, ['GET', 'POST']));

// Создаем роутер и запускаем
$router = new Router();

try {
    echo "=== Basic Router Example ===\n\n";
    
    echo "Available routes:\n";
    echo "- / (Home page)\n";
    echo "- /about (About page)\n";
    echo "- /users (User list)\n";
    echo "- /users/create (Create user form)\n";
    echo "- /users/{id} (User profile, e.g., /users/123)\n";
    echo "- /hello (Hello world)\n";
    echo "- /hello/{name} (Personal greeting, e.g., /hello/John)\n";
    echo "- /greet/{greeting}/{name} (Custom greeting, e.g., /greet/Hi/Alice)\n";
    echo "- /contact (Contact form - supports GET and POST)\n\n";
    
    echo "To test different routes, use:\n";
    echo "REQUEST_URI=\"/users\" php basic-example.php\n";
    echo "REQUEST_URI=\"/users/123\" php basic-example.php\n";
    echo "REQUEST_URI=\"/hello/World\" php basic-example.php\n\n";
    
    echo "==================================================\n\n";
    
    $router->setCollection($routes)->run();
    
    echo "\n\n=== Basic Example Complete ===\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "This route might not exist or might use a different HTTP method.\n";
} 