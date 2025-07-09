<?php

require __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;
use FaustVik\Router\Validation\ParameterValidationRule;

// Простой сервис для демонстрации
class Logger
{
    private string $logFile;

    public function __construct(string $logFile = 'app.log')
    {
        $this->logFile = $logFile;
    }

    public function log(string $message): void
    {
        echo "[LOG] {$message}\n";
    }
}

// Сервис для работы с пользователями
class UserService
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function getUser(int $id): array
    {
        $this->logger->log("Getting user with ID: {$id}");
        return [
            'id' => $id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];
    }

    public function getAllUsers(): array
    {
        $this->logger->log("Getting all users");
        return [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']
        ];
    }
}

// Контроллер использующий DI
class UserController
{
    private UserService $userService;
    private Logger $logger;

    public function __construct(UserService $userService, Logger $logger)
    {
        $this->userService = $userService;
        $this->logger = $logger;
    }

    public function index(): void
    {
        $this->logger->log("UserController::index called");
        $users = $this->userService->getAllUsers();

        echo json_encode([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function show(int $id): void
    {
        $this->logger->log("UserController::show called with ID: {$id}");
        $user = $this->userService->getUser($id);

        echo json_encode([
            'status' => 'success',
            'data' => $user
        ]);
    }

    public function create(Request $request): void
    {
        $this->logger->log("UserController::create called");
        echo json_encode([
            'status' => 'success',
            'message' => 'User created successfully'
        ]);
    }
}

// Функция для создания настроенного роутера
function createRouter(): Router
{
    $router = new Router();

    // Включаем DI с конфигурацией
    $router->enableDI(function ($container) {
        // Настройка зависимостей
        $container->singleton(Logger::class, function () {
            return new Logger('app.log');
        });

        $container->bind(UserService::class, function ($container) {
            return new UserService($container->get(Logger::class));
        });
    });

    // Создание коллекции маршрутов
    $routes = new RoutesCollection();

    // Добавляем маршруты - контроллеры будут автоматически созданы через DI
    $routes->addGet('/users', UserController::class, 'index');
    $routes->addGet('/users/{id}', UserController::class, 'show')
        ->validate([
            ParameterValidationRule::for('id')->regex('/^\d+$/')
        ]);
    $routes->addPost('/users', UserController::class, 'create');

    // Настройка роутера
    $router->setCollection($routes);

    return $router;
}

// Демонстрация работы
echo "=== Демонстрация DI с Router ===\n\n";

// Тестирование разных маршрутов
echo "1. GET /users:\n";
$router = createRouter();
$router->setUri('/users');
$_SERVER['REQUEST_METHOD'] = 'GET';
$router->run();

echo "\n\n2. GET /users/123:\n";
$router = createRouter();
$router->setUri('/users/123');
$_SERVER['REQUEST_METHOD'] = 'GET';
$router->run();

echo "\n\n3. POST /users:\n";
$router = createRouter();
$router->setUri('/users');
$_SERVER['REQUEST_METHOD'] = 'POST';
try {
    $router->run();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n\n=== Информация о DI контейнере ===\n";
echo "DI включен: " . ($router->isDIEnabled() ? 'Да' : 'Нет') . "\n";
echo "Контейнер: " . get_class($router->getContainer()) . "\n";
