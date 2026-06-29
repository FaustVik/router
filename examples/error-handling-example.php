<?php

declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Http\Response;
use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RouteAnonymousFunc;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;

// Кастомные исключения
class ValidationException extends Exception
{
    public function __construct(array $errors, string $message = 'Validation failed')
    {
        $this->errors = $errors;
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors ?? [];
    }

    private array $errors = [];
}

class DatabaseException extends Exception
{
    public function __construct(string $message = 'Database error occurred', int $code = 500)
    {
        parent::__construct($message, $code);
    }
}

class AuthenticationException extends Exception
{
    public function __construct(string $message = 'Authentication required', int $code = 401)
    {
        parent::__construct($message, $code);
    }
}

class AuthorizationException extends Exception
{
    public function __construct(string $message = 'Access denied', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}

class NotFoundException extends Exception
{
    public function __construct(string $message = 'Resource not found', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}

class RateLimitException extends Exception
{
    public function __construct(string $message = 'Rate limit exceeded', int $code = 429)
    {
        parent::__construct($message, $code);
    }
}

// Контроллер для демонстрации различных ошибок
class ErrorController
{
    public function phpError(): Response
    {
        // Намеренная ошибка PHP
        $array = ['key' => 'value'];
        $result = $array['nonexistent_key']['nested']; // Ошибка undefined index

        return Response::json(['result' => $result]);
    }

    public function divisionByZero(): Response
    {
        $number = 10;
        $divisor = 0;
        $result = $number / $divisor; // Warning

        return Response::json(['result' => $result]);
    }

    public function validationError(): Response
    {
        $errors = [
            'name' => ['Name is required', 'Name must be at least 2 characters'],
            'email' => ['Email is required', 'Email format is invalid'],
            'age' => ['Age must be a positive number'],
        ];

        throw new ValidationException($errors, 'User input validation failed');
    }

    public function databaseError(): Response
    {
        throw new DatabaseException('Unable to connect to database server');
    }

    public function authenticationError(): Response
    {
        throw new AuthenticationException('Invalid credentials provided');
    }

    public function authorizationError(): Response
    {
        throw new AuthorizationException('You do not have permission to access this resource');
    }

    public function notFoundError(): Response
    {
        throw new NotFoundException('User with ID 999 was not found');
    }

    public function rateLimitError(): Response
    {
        throw new RateLimitException('API rate limit exceeded. Try again in 60 seconds');
    }

    public function serverError(): Response
    {
        throw new Exception('Internal server error occurred');
    }

    public function customStatusCode($code): Response
    {
        $statusCode = (int) $code;

        $errorMessages = [
            400 => 'Bad Request - Invalid request format',
            401 => 'Unauthorized - Authentication required',
            403 => 'Forbidden - Access denied',
            404 => 'Not Found - Resource does not exist',
            405 => 'Method Not Allowed - HTTP method not supported',
            422 => 'Unprocessable Entity - Validation failed',
            429 => 'Too Many Requests - Rate limit exceeded',
            500 => 'Internal Server Error - Something went wrong',
            501 => 'Not Implemented - Feature not available',
            502 => 'Bad Gateway - Upstream server error',
            503 => 'Service Unavailable - Server temporarily down',
        ];

        $message = $errorMessages[$statusCode] ?? 'Unknown Error';

        return Response::json([
            'error' => true,
            'status' => $statusCode,
            'message' => $message,
            'timestamp' => date('c'),
            'details' => "This is a simulation of HTTP $statusCode error",
        ], $statusCode);
    }

    public function errorWithDetails($type): Response
    {
        switch ($type) {
            case 'network':
                return Response::json([
                    'error' => 'network_error',
                    'message' => 'Network connection failed',
                    'details' => [
                        'timeout' => 5000,
                        'retry_after' => 30,
                        'endpoint' => 'https://api.example.com',
                    ],
                    'troubleshooting' => [
                        'Check your internet connection',
                        'Verify the endpoint URL',
                        'Try again in a few minutes',
                    ],
                ], 503);

            case 'file':
                return Response::json([
                    'error' => 'file_error',
                    'message' => 'File operation failed',
                    'details' => [
                        'file_path' => '/uploads/document.pdf',
                        'error_code' => 'PERMISSION_DENIED',
                        'size_limit' => '10MB',
                    ],
                    'solutions' => [
                        'Check file permissions',
                        'Verify file size is under limit',
                        'Ensure file format is supported',
                    ],
                ], 422);

            case 'payment':
                return Response::json([
                    'error' => 'payment_failed',
                    'message' => 'Payment processing failed',
                    'details' => [
                        'transaction_id' => 'txn_' . uniqid(),
                        'decline_code' => 'insufficient_funds',
                        'amount' => '$99.99',
                    ],
                    'next_steps' => [
                        'Check your account balance',
                        'Contact your bank',
                        'Try a different payment method',
                    ],
                ], 402);

            default:
                return Response::json([
                    'error' => 'unknown_error_type',
                    'message' => "Unknown error type: $type",
                    'available_types' => ['network', 'file', 'payment'],
                ], 400);
        }
    }
}

// Контроллер для тестирования ограничений методов
class MethodTestController
{
    public function getOnly(): Response
    {
        return Response::json(['message' => 'This endpoint only accepts GET requests']);
    }

    public function postOnly(): Response
    {
        return Response::json(['message' => 'This endpoint only accepts POST requests']);
    }

    public function putOnly(): Response
    {
        return Response::json(['message' => 'This endpoint only accepts PUT requests']);
    }

    public function deleteOnly(): Response
    {
        return Response::json(['message' => 'This endpoint only accepts DELETE requests']);
    }
}

// Создаем коллекцию маршрутов
$routes = new RoutesCollection();

// === PHP Errors ===
$routes->set(Route::create('/errors/php', ErrorController::class, 'phpError', [], ['GET']));
$routes->set(Route::create('/errors/division', ErrorController::class, 'divisionByZero', [], ['GET']));

// === Application Exceptions ===
$routes->set(Route::create('/errors/validation', ErrorController::class, 'validationError', [], ['GET']));
$routes->set(Route::create('/errors/database', ErrorController::class, 'databaseError', [], ['GET']));
$routes->set(Route::create('/errors/auth', ErrorController::class, 'authenticationError', [], ['GET']));
$routes->set(Route::create('/errors/forbidden', ErrorController::class, 'authorizationError', [], ['GET']));
$routes->set(Route::create('/errors/not-found', ErrorController::class, 'notFoundError', [], ['GET']));
$routes->set(Route::create('/errors/rate-limit', ErrorController::class, 'rateLimitError', [], ['GET']));
$routes->set(Route::create('/errors/server', ErrorController::class, 'serverError', [], ['GET']));

// === HTTP Status Codes ===
$routes->set(Route::create('/errors/status/{code}', ErrorController::class, 'customStatusCode', [], ['GET']));

// === Error Details ===
$routes->set(Route::create('/errors/details/{type}', ErrorController::class, 'errorWithDetails', [], ['GET']));

// === Method Restrictions ===
$routes->set(Route::create('/methods/get-only', MethodTestController::class, 'getOnly', [], ['GET']));
$routes->set(Route::create('/methods/post-only', MethodTestController::class, 'postOnly', [], ['POST']));
$routes->set(Route::create('/methods/put-only', MethodTestController::class, 'putOnly', [], ['PUT']));
$routes->set(Route::create('/methods/delete-only', MethodTestController::class, 'deleteOnly', [], ['DELETE']));

// === Global Error Handler ===
$routes->set(RouteAnonymousFunc::create('/errors/handler-demo', static function (): Response {
    return Response::json([
        'message' => 'Error handling demonstration',
        'note' => 'This route will trigger the global error handler if an exception is thrown',
        'examples' => [
            'Try accessing a non-existent route',
            'Use wrong HTTP method',
            'Trigger validation errors',
            'Simulate server errors',
        ],
        'error_types' => [
            'PHP errors (undefined variables, type errors)',
            'Custom exceptions (ValidationException, etc.)',
            'HTTP status errors (401, 403, 404, etc.)',
            'Method not allowed errors',
        ],
    ]);
}, ['GET']));

// Создаем роутер и запускаем
$router = new Router();

try {
    echo "=== Error Handling Example ===\n\n";

    echo "Comprehensive error handling and exception management:\n\n";

    echo "🔧 PHP Errors:\n";
    echo "   /errors/php - Undefined array key error\n";
    echo "   /errors/division - Division by zero warning\n\n";

    echo "📋 Application Exceptions:\n";
    echo "   /errors/validation - Validation errors (422)\n";
    echo "   /errors/database - Database connection error (500)\n";
    echo "   /errors/auth - Authentication error (401)\n";
    echo "   /errors/forbidden - Authorization error (403)\n";
    echo "   /errors/not-found - Resource not found (404)\n";
    echo "   /errors/rate-limit - Rate limit exceeded (429)\n";
    echo "   /errors/server - Generic server error (500)\n\n";

    echo "🌐 HTTP Status Codes:\n";
    echo "   /errors/status/{code} - Custom status codes\n";
    echo "     Examples: /errors/status/400, /errors/status/503\n\n";

    echo "🔍 Detailed Error Information:\n";
    echo "   /errors/details/network - Network error with details\n";
    echo "   /errors/details/file - File operation error\n";
    echo "   /errors/details/payment - Payment processing error\n\n";

    echo "🚫 Method Restrictions:\n";
    echo "   /methods/get-only - Only allows GET\n";
    echo "   /methods/post-only - Only allows POST\n";
    echo "   /methods/put-only - Only allows PUT\n";
    echo "   /methods/delete-only - Only allows DELETE\n\n";

    echo "🛠️ Error Handler Demo:\n";
    echo "   /errors/handler-demo - Error handling information\n\n";

    echo "🧪 Test commands:\n";
    echo "REQUEST_URI=\"/errors/validation\" php error-handling-example.php\n";
    echo "REQUEST_URI=\"/errors/auth\" php error-handling-example.php\n";
    echo "REQUEST_URI=\"/errors/status/404\" php error-handling-example.php\n";
    echo "REQUEST_URI=\"/errors/details/payment\" php error-handling-example.php\n\n";

    echo "# Test method restrictions:\n";
    echo "REQUEST_METHOD=\"POST\" REQUEST_URI=\"/methods/get-only\" php error-handling-example.php\n";
    echo "REQUEST_METHOD=\"GET\" REQUEST_URI=\"/methods/post-only\" php error-handling-example.php\n\n";

    echo "# Test non-existent routes:\n";
    echo "REQUEST_URI=\"/non-existent-route\" php error-handling-example.php\n\n";

    echo "============================================================\n\n";

    $router->setCollection($routes)->run();

    echo "\n\n=== Error Handling Example Complete ===\n";
} catch (ValidationException $e) {
    echo "\n❌ Validation Error:\n";
    echo 'Message: ' . $e->getMessage() . "\n";
    echo "Errors:\n";
    foreach ($e->getErrors() as $field => $fieldErrors) {
        echo "  $field:\n";
        foreach ($fieldErrors as $error) {
            echo "    - $error\n";
        }
    }
} catch (AuthenticationException $e) {
    echo "\n🔒 Authentication Error: " . $e->getMessage() . "\n";
    echo 'HTTP Status: ' . $e->getCode() . "\n";
} catch (AuthorizationException $e) {
    echo "\n⛔ Authorization Error: " . $e->getMessage() . "\n";
    echo 'HTTP Status: ' . $e->getCode() . "\n";
} catch (NotFoundException $e) {
    echo "\n🔍 Not Found Error: " . $e->getMessage() . "\n";
    echo 'HTTP Status: ' . $e->getCode() . "\n";
} catch (RateLimitException $e) {
    echo "\n⏱️  Rate Limit Error: " . $e->getMessage() . "\n";
    echo 'HTTP Status: ' . $e->getCode() . "\n";
} catch (DatabaseException $e) {
    echo "\n💾 Database Error: " . $e->getMessage() . "\n";
    echo 'HTTP Status: ' . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "\n❌ Unexpected Error: " . $e->getMessage() . "\n";
    echo 'Type: ' . get_class($e) . "\n";
    echo 'File: ' . $e->getFile() . ':' . $e->getLine() . "\n";

    // В продакшене не показывайте stack trace
    if (getenv('APP_DEBUG') === 'true') {
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
}
