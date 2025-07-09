<?php

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RouteAnonymousFunc;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;

// Контроллеры для демонстрации Response типов
class ApiController 
{
    public function getUserJson(Request $request, $id): Response
    {
        $userData = [
            'id' => (int) $id,
            'name' => "User #$id",
            'email' => "user$id@example.com",
            'created_at' => date('Y-m-d H:i:s'),
            'meta' => [
                'request_method' => $request->getMethod(),
                'request_uri' => $request->getUri()
            ]
        ];
        
        return Response::json($userData);
    }
    
    public function getUsersList(): Response
    {
        $users = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com']
        ];
        
        return Response::json($users, 200);
    }
    
    public function errorDemo(): Response
    {
        return Response::json([
            'error' => 'Something went wrong',
            'code' => 'DEMO_ERROR',
            'timestamp' => date('c')
        ], 500);
    }
    
    public function notFoundDemo(): Response
    {
        return Response::json([
            'error' => 'Resource not found',
            'code' => 'NOT_FOUND',
            'message' => 'The requested resource does not exist'
        ], 404);
    }
}

class PageController 
{
    public function homepage(): Response
    {
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Router Example</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #333; }
        .features { background: #f5f5f5; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 FaustVik Router Example</h1>
        <div class="features">
            <h2>Features:</h2>
            <ul>
                <li>URL Parameters</li>
                <li>Middleware Support</li>
                <li>JSON/HTML Responses</li>
                <li>Route Groups</li>
                <li>Route Caching</li>
            </ul>
        </div>
        <p><strong>Generated at:</strong> ' . date('Y-m-d H:i:s') . '</p>
    </div>
</body>
</html>';
        
        return Response::html($html);
    }
    
    public function aboutPage(): Response
    {
        $html = '<h1>About Page</h1>
<p>This is a simple HTML response from the router.</p>
<p>Current time: ' . date('Y-m-d H:i:s') . '</p>
<a href="/">← Back to Home</a>';
        
        return Response::html($html);
    }
}

// Создаем коллекцию маршрутов
$routes = new RoutesCollection();

// === JSON Responses ===
$routes->set(Route::create('/api/users', ApiController::class, 'getUsersList', [], ['GET']));
$routes->set(Route::create('/api/users/{id}', ApiController::class, 'getUserJson', [], ['GET']));
$routes->set(Route::create('/api/error', ApiController::class, 'errorDemo', [], ['GET']));
$routes->set(Route::create('/api/not-found', ApiController::class, 'notFoundDemo', [], ['GET']));

// === HTML Responses ===
$routes->set(Route::create('/', PageController::class, 'homepage', [], ['GET']));
$routes->set(Route::create('/about', PageController::class, 'aboutPage', [], ['GET']));

// === Redirect Responses ===
$routes->set(RouteAnonymousFunc::create('/redirect-demo', static function (): Response {
    return Response::redirect('/about');
}, ['GET']));

$routes->set(RouteAnonymousFunc::create('/redirect-external', static function (): Response {
    return Response::redirect('https://github.com', 302);
}, ['GET']));

$routes->set(RouteAnonymousFunc::create('/redirect-permanent', static function (): Response {
    return Response::redirect('/about', 301);
}, ['GET']));

// === Custom Headers ===
$routes->set(RouteAnonymousFunc::create('/custom-headers', static function (): Response {
    $data = [
        'message' => 'Response with custom headers',
        'timestamp' => date('c')
    ];
    
    $response = Response::json($data);
    $response->header('X-Custom-Header', 'MyCustomValue');
    $response->header('X-API-Version', '1.0');
    $response->header('X-Rate-Limit', '1000');
    
    return $response;
}, ['GET']));

// === Status Codes ===
$routes->set(RouteAnonymousFunc::create('/status/{code}', static function ($code): Response {
    $statusMessages = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error'
    ];
    
    $statusCode = (int)$code;
    $message = $statusMessages[$statusCode] ?? 'Unknown Status';
    
    return Response::json([
        'status' => $statusCode,
        'message' => $message,
        'description' => "This is a demo of HTTP $statusCode status code"
    ], $statusCode);
}, ['GET']));

// === File Download Simulation ===
$routes->set(RouteAnonymousFunc::create('/download', static function (): Response {
    $content = "This is a sample file content.\nGenerated at: " . date('Y-m-d H:i:s');
    
    $response = Response::create($content, 200);
    $response->header('Content-Type', 'application/octet-stream');
    $response->header('Content-Disposition', 'attachment; filename="sample.txt"');
    $response->header('Content-Length', (string)strlen($content));
    
    return $response;
}, ['GET']));

// === XML Response ===
$routes->set(RouteAnonymousFunc::create('/xml', static function (): Response {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<response>
    <message>XML Response Example</message>
    <timestamp>' . date('c') . '</timestamp>
    <data>
        <users>
            <user id="1">John Doe</user>
            <user id="2">Jane Smith</user>
        </users>
    </data>
</response>';
    
    $response = Response::create($xml, 200);
    $response->header('Content-Type', 'application/xml');
    
    return $response;
}, ['GET']));

// === Plain Text Response ===
$routes->set(RouteAnonymousFunc::create('/text', static function (): Response {
    $text = "Plain Text Response\n";
    $text .= "===================\n\n";
    $text .= "This is a simple text response.\n";
    $text .= "Generated at: " . date('Y-m-d H:i:s') . "\n";
    $text .= "Content-Type: text/plain\n";
    
    $response = Response::create($text, 200);
    $response->header('Content-Type', 'text/plain');
    
    return $response;
}, ['GET']));

// === CORS Response ===
$routes->set(RouteAnonymousFunc::create('/cors', static function (): Response {
    $data = [
        'message' => 'CORS enabled response',
        'timestamp' => date('c'),
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE']
    ];
    
    $response = Response::json($data);
    $response->header('Access-Control-Allow-Origin', '*');
    $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    
    return $response;
}, ['GET']));

// Создаем роутер и запускаем
$router = new Router();

try {
    echo "=== Response Types Example ===\n\n";
    
    echo "Available response types:\n\n";
    
    echo "1. JSON Responses:\n";
    echo "   /api/users - List of users (JSON)\n";
    echo "   /api/users/{id} - Single user (JSON with Request data)\n";
    echo "   /api/error - Error response (500 status)\n";
    echo "   /api/not-found - Not found response (404 status)\n\n";
    
    echo "2. HTML Responses:\n";
    echo "   / - Homepage (full HTML document)\n";
    echo "   /about - About page (simple HTML)\n\n";
    
    echo "3. Redirects:\n";
    echo "   /redirect-demo - Redirect to /about (302)\n";
    echo "   /redirect-external - Redirect to GitHub (302)\n";
    echo "   /redirect-permanent - Permanent redirect (301)\n\n";
    
    echo "4. Custom Headers:\n";
    echo "   /custom-headers - Response with custom headers\n\n";
    
    echo "5. Status Codes:\n";
    echo "   /status/{code} - Custom status codes (e.g., /status/201)\n\n";
    
    echo "6. File Download:\n";
    echo "   /download - File download simulation\n\n";
    
    echo "7. XML Response:\n";
    echo "   /xml - XML formatted response\n\n";
    
    echo "8. Plain Text:\n";
    echo "   /text - Plain text response\n\n";
    
    echo "9. CORS Response:\n";
    echo "   /cors - Response with CORS headers\n\n";
    
    echo "Test commands:\n";
    echo "REQUEST_URI=\"/api/users\" php response-types-example.php\n";
    echo "REQUEST_URI=\"/api/users/123\" php response-types-example.php\n";
    echo "REQUEST_URI=\"/status/404\" php response-types-example.php\n";
    echo "REQUEST_URI=\"/custom-headers\" php response-types-example.php\n";
    echo "REQUEST_URI=\"/\" php response-types-example.php\n\n";
    
    echo "============================================================\n\n";
    
    $router->setCollection($routes)->run();
    
    echo "\n\n=== Response Types Example Complete ===\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "This route might not exist. Try one of the listed routes above.\n";
} 