<?php

declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Interfaces\Middleware\MiddlewareInterface;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;

// Интерфейс для аутентификации
interface AuthServiceInterface
{
    public function authenticate(string $token): bool;

    public function getUser(string $token): array;
}

// Реализация сервиса аутентификации
class AuthService implements AuthServiceInterface
{
    private array $tokens = [
        'admin-token' => ['id' => 1, 'role' => 'admin', 'name' => 'Admin User'],
        'user-token' => ['id' => 2, 'role' => 'user', 'name' => 'Regular User'],
    ];

    public function authenticate(string $token): bool
    {
        return isset($this->tokens[$token]);
    }

    public function getUser(string $token): array
    {
        return $this->tokens[$token] ?? [];
    }
}

// Middleware для аутентификации с DI
class AuthMiddleware implements MiddlewareInterface
{
    private AuthServiceInterface $authService;

    public function __construct(AuthServiceInterface $authService)
    {
        $this->authService = $authService;
    }

    public function handle(Request $request, callable $next): Response
    {
        $token = $request->getHeader('Authorization') ?? $request->getQueryParam('token');

        if (!$token) {
            return new Response('Unauthorized: No token provided', 401);
        }

        if (!$this->authService->authenticate($token)) {
            return new Response('Unauthorized: Invalid token', 401);
        }

        // Добавляем пользователя в запрос
        $user = $this->authService->getUser($token);
        $request = $request->withAttribute('user', $user);

        return $next($request);
    }
}

// Логгер для аудита
class AuditLogger
{
    public function log(string $action, array $user): void
    {
        echo "[AUDIT] User {$user['name']} ({$user['role']}) performed: {$action}\n";
    }
}

// Контроллер для администрирования
class AdminController
{
    private AuthServiceInterface $authService;
    private AuditLogger $auditLogger;

    public function __construct(AuthServiceInterface $authService, AuditLogger $auditLogger)
    {
        $this->authService = $authService;
        $this->auditLogger = $auditLogger;
    }

    public function dashboard(Request $request): void
    {
        $user = $request->getAttribute('user');
        $this->auditLogger->log('accessed admin dashboard', $user);

        echo json_encode([
            'status' => 'success',
            'message' => 'Admin dashboard',
            'user' => $user,
        ]);
    }

    public function users(Request $request): void
    {
        $user = $request->getAttribute('user');
        $this->auditLogger->log('viewed user list', $user);

        echo json_encode([
            'status' => 'success',
            'data' => [
                ['id' => 1, 'name' => 'Admin User', 'role' => 'admin'],
                ['id' => 2, 'name' => 'Regular User', 'role' => 'user'],
            ],
            'user' => $user,
        ]);
    }
}

// Создание роутера с DI
$router = new Router();

// Настройка DI контейнера
$router->enableDI(function ($container): void {
    // Сингleton для аутентификации
    $container->singleton(AuthServiceInterface::class, function () {
        return new AuthService();
    });

    // Сингleton для логгера аудита
    $container->singleton(AuditLogger::class, function () {
        return new AuditLogger();
    });

    // Middleware будет создан через DI
    $container->bind(AuthMiddleware::class, function ($container) {
        return new AuthMiddleware($container->get(AuthServiceInterface::class));
    });

    // Контроллер будет создан через DI
    $container->bind(AdminController::class, function ($container) {
        return new AdminController(
            $container->get(AuthServiceInterface::class),
            $container->get(AuditLogger::class)
        );
    });
});

// Создание коллекции маршрутов
$routes = new RoutesCollection();

// Создаем middleware instance через DI
$authMiddleware = $router->getContainer()->resolve(AuthMiddleware::class);

// Защищенные маршруты с middleware
$routes->addGet('/admin/dashboard', AdminController::class, 'dashboard')
    ->middleware([$authMiddleware]);

$routes->addGet('/admin/users', AdminController::class, 'users')
    ->middleware([$authMiddleware]);

// Настройка роутера
$router->setCollection($routes);

// Демонстрация работы
echo "=== Демонстрация DI с Middleware ===\n\n";

// Тестирование без токена
echo "1. Доступ без токена:\n";
$router->setUri('/admin/dashboard');
$_SERVER['REQUEST_METHOD'] = 'GET';
$router->run();

echo "\n\n2. Доступ с неверным токеном:\n";
$router->setUri('/admin/dashboard?token=invalid-token');
$_SERVER['REQUEST_METHOD'] = 'GET';
$router->run();

echo "\n\n3. Доступ с правильным токеном (admin):\n";
$router->setUri('/admin/dashboard?token=admin-token');
$_SERVER['REQUEST_METHOD'] = 'GET';
$router->run();

echo "\n\n4. Доступ к списку пользователей:\n";
$router->setUri('/admin/users?token=admin-token');
$_SERVER['REQUEST_METHOD'] = 'GET';
$router->run();

echo "\n\n5. Доступ обычного пользователя:\n";
$router->setUri('/admin/dashboard?token=user-token');
$_SERVER['REQUEST_METHOD'] = 'GET';
$router->run();

echo "\n\n=== Информация о DI ===\n";
echo 'DI включен: ' . ($router->isDIEnabled() ? 'Да' : 'Нет') . "\n";
echo 'Может создать AuthMiddleware: ' . ($router->getContainer()->canResolve(AuthMiddleware::class) ? 'Да' : 'Нет') . "\n";
echo 'Может создать AdminController: ' . ($router->getContainer()->canResolve(AdminController::class) ? 'Да' : 'Нет') . "\n";
