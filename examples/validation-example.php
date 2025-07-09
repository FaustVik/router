<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;
use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Validation\ParameterValidationRule;
use FaustVik\Router\Validation\IntValidator;
use FaustVik\Router\Validation\StringValidator;
use FaustVik\Router\Validation\EmailValidator;
use FaustVik\Router\Validation\UuidValidator;
use FaustVik\Router\Validation\SlugValidator;
use FaustVik\Router\Validation\RegexValidator;

/**
 * Контроллеры для демонстрации валидации
 */
class UserController
{
    public function show(Request $request): Response
    {
        $id = $request->getParam('id');
        return Response::json(['message' => "User ID: $id (validated as integer)"]);
    }

    public function profile(Request $request): Response
    {
        $slug = $request->getParam('slug');
        return Response::json(['message' => "User profile: $slug (validated as slug)"]);
    }

    public function avatar(Request $request): Response
    {
        $uuid = $request->getParam('uuid');
        return Response::json(['message' => "Avatar UUID: $uuid (validated as UUID)"]);
    }

    public function posts(Request $request): Response
    {
        $userId = $request->getParam('userId');
        $postId = $request->getParam('postId');
        return Response::json([
            'message' => "User $userId, Post $postId (both validated as positive integers)"
        ]);
    }

    public function contact(Request $request): Response
    {
        $email = $request->getParam('email');
        return Response::json(['message' => "Contact email: $email (validated as email)"]);
    }

    public function create(Request $request): Response
    {
        $name = $request->getParam('name');
        $age = $request->getParam('age');
        return Response::json([
            'message' => "Create user: $name, age: $age (name: 2-50 chars, age: 18-100)"
        ]);
    }
}

class ProductController
{
    public function show(Request $request): Response
    {
        $category = $request->getParam('category');
        $code = $request->getParam('code');
        return Response::json([
            'message' => "Product: $category/$code (category: slug, code: format ABC-123)"
        ]);
    }
}

// Создаем коллекцию маршрутов
$routes = new RoutesCollection();

// === 1. БАЗОВАЯ ВАЛИДАЦИЯ ===

// Валидация ID как положительного числа
$routes->addGet('/users/{id}', UserController::class, 'show')
    ->validate([
        ParameterValidationRule::for('id')
            ->int(['min' => 1])
    ]);

// Валидация slug (только буквы, цифры, дефисы, подчеркивания)
$routes->addGet('/users/profile/{slug}', UserController::class, 'profile')
    ->validate([
        ParameterValidationRule::for('slug')
            ->slug(['min_length' => 3, 'max_length' => 50])
    ]);

// Валидация UUID
$routes->addGet('/users/avatar/{uuid}', UserController::class, 'avatar')
    ->validate([
        ParameterValidationRule::for('uuid')
            ->uuid()
    ]);

// Валидация email
$routes->addGet('/contact/{email}', UserController::class, 'contact')
    ->validate([
        ParameterValidationRule::for('email')
            ->email()
    ]);

// === 2. КОМПЛЕКСНАЯ ВАЛИДАЦИЯ ===

// Множественная валидация
$routes->addGet('/users/{userId}/posts/{postId}', UserController::class, 'posts')
    ->validate([
        ParameterValidationRule::for('userId')
            ->int(['min' => 1, 'max' => 999999]),
        ParameterValidationRule::for('postId')
            ->int(['min' => 1])
    ]);

// Валидация строки с ограничениями
$routes->addPost('/users/{name}/age/{age}', UserController::class, 'create')
    ->validate([
        ParameterValidationRule::for('name')
            ->string(['min_length' => 2, 'max_length' => 50])
            ->regex('/^[a-zA-Z\s]+$/', 'Name must contain only letters and spaces'),
        ParameterValidationRule::for('age')
            ->int(['min' => 18, 'max' => 100])
    ]);

// Кастомная валидация с регулярными выражениями
$routes->addGet('/products/{category}/{code}', ProductController::class, 'show')
    ->validate([
        ParameterValidationRule::for('category')
            ->slug(['min_length' => 2, 'max_length' => 20]),
        ParameterValidationRule::for('code')
            ->regex('/^[A-Z]{3}-\d{3}$/', 'Code must be in format ABC-123')
    ]);

// === 3. ВАЛИДАЦИЯ В ГРУППАХ ===

// Группа с общими правилами валидации
$routes->prefix('/api/v1')->validate([
    ParameterValidationRule::for('id')
        ->int(['min' => 1])
])->group(function($group) {
    $group->get('/users/{id}', UserController::class, 'show');
    $group->getFunc('/posts/{id}', function(Request $request): Response {
        $id = $request->getParam('id');
        return Response::json(['message' => "Post ID: $id (validated by group)"]);
    });
    
    // Дополнительная валидация в подгруппе
    $group->prefix('/admin')->validate([
        ParameterValidationRule::for('token')
            ->regex('/^[a-f0-9]{32}$/', 'Token must be 32 hex characters')
    ])->group(function($adminGroup) {
        $adminGroup->getFunc('/users/{id}/token/{token}', function(Request $request): Response {
            $id = $request->getParam('id');
            $token = $request->getParam('token');
            return Response::json([
                'message' => "Admin access: User $id, Token $token (double validation)"
            ]);
        });
    });
});

// === 4. ОПЦИОНАЛЬНЫЕ ПАРАМЕТРЫ ===

// Некоторые параметры могут быть опциональными
$routes->addGetFunc('/search/{query?}', function(Request $request): Response {
    $query = $request->getParam('query') ?? 'empty';
    return Response::json(['query' => $query]);
})->validate([
    ParameterValidationRule::for('query')
        ->string(['min_length' => 2, 'max_length' => 100])
        ->optional()
]);

// === 5. КАСТОМНЫЕ ВАЛИДАТОРЫ ===

// Создание кастомного валидатора
class PhoneValidator implements \FaustVik\Router\interfaces\Validation\ParameterValidatorInterface
{
    private string $errorMessage = '';

    public function validate(string $value, array $options = []): bool
    {
        $pattern = '/^\+?[1-9]\d{1,14}$/';
        if (!preg_match($pattern, $value)) {
            $this->errorMessage = 'Invalid phone number format';
            return false;
        }
        return true;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function getName(): string
    {
        return 'phone';
    }
}

$routes->addGetFunc('/contact/phone/{phone}', function(Request $request): Response {
    $phone = $request->getParam('phone');
    return Response::json(['message' => "Phone: $phone (custom validation)"]);
})->validate([
    ParameterValidationRule::for('phone')
        ->custom(new PhoneValidator())
]);

// Создаем и запускаем роутер
$router = new Router();
$router->setCollection($routes);

// Показываем информацию о тестах
echo "=== ВАЛИДАЦИЯ ПАРАМЕТРОВ РОУТОВ ===\n";
echo "Примеры команд для тестирования:\n\n";

echo "✅ Валидные запросы:\n";
echo "REQUEST_METHOD=\"GET\" REQUEST_URI=\"/users/123\" php validation-example.php\n";
echo "REQUEST_METHOD=\"GET\" REQUEST_URI=\"/users/profile/john-doe\" php validation-example.php\n";
echo "REQUEST_METHOD=\"GET\" REQUEST_URI=\"/users/avatar/550e8400-e29b-41d4-a716-446655440000\" php validation-example.php\n";
echo "REQUEST_METHOD=\"GET\" REQUEST_URI=\"/contact/user@example.com\" php validation-example.php\n";
echo "REQUEST_METHOD=\"GET\" REQUEST_URI=\"/products/electronics/ABC-123\" php validation-example.php\n";
echo "REQUEST_METHOD=\"GET\" REQUEST_URI=\"/api/v1/users/456\" php validation-example.php\n";

echo "\n❌ Невалидные запросы:\n";
echo "REQUEST_METHOD=\"GET\" REQUEST_URI=\"/users/0\" php validation-example.php (ID должно быть > 0)\n";
echo "REQUEST_METHOD=\"GET\" REQUEST_URI=\"/users/abc\" php validation-example.php (ID должно быть числом)\n";
echo "REQUEST_METHOD=\"GET\" REQUEST_URI=\"/users/profile/a\" php validation-example.php (slug слишком короткий)\n";
echo "REQUEST_METHOD=\"GET\" REQUEST_URI=\"/contact/invalid-email\" php validation-example.php (неверный email)\n";
echo "REQUEST_METHOD=\"GET\" REQUEST_URI=\"/products/electronics/invalid-code\" php validation-example.php (неверный формат кода)\n";

echo "\n" . str_repeat("=", 50) . "\n";

// Запускаем роутер
$router->run(); 