<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Route\Route;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;

// Симуляция базы данных в памяти
class Database
{
    private static array $users = [
        1 => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'role' => 'admin'],
        2 => ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'role' => 'user'],
        3 => ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'role' => 'user']
    ];

    private static array $posts = [
        1 => ['id' => 1, 'title' => 'First Post', 'content' => 'Hello World', 'user_id' => 1],
        2 => ['id' => 2, 'title' => 'Second Post', 'content' => 'REST API Demo', 'user_id' => 2],
        3 => ['id' => 3, 'title' => 'Third Post', 'content' => 'Router Example', 'user_id' => 1]
    ];

    private static int $nextUserId = 4;
    private static int $nextPostId = 4;

    public static function getAllUsers(): array
    {
        return array_values(self::$users);
    }

    public static function getUser(int $id): ?array
    {
        return self::$users[$id] ?? null;
    }

    public static function createUser(array $data): array
    {
        $user = [
            'id' => self::$nextUserId++,
            'name' => $data['name'] ?? 'Unknown',
            'email' => $data['email'] ?? 'unknown@example.com',
            'role' => $data['role'] ?? 'user',
            'created_at' => date('Y-m-d H:i:s')
        ];

        self::$users[$user['id']] = $user;
        return $user;
    }

    public static function updateUser(int $id, array $data): ?array
    {
        if (!isset(self::$users[$id])) {
            return null;
        }

        $user = self::$users[$id];
        $user['name'] = $data['name'] ?? $user['name'];
        $user['email'] = $data['email'] ?? $user['email'];
        $user['role'] = $data['role'] ?? $user['role'];
        $user['updated_at'] = date('Y-m-d H:i:s');

        self::$users[$id] = $user;
        return $user;
    }

    public static function deleteUser(int $id): bool
    {
        if (isset(self::$users[$id])) {
            unset(self::$users[$id]);
            return true;
        }
        return false;
    }

    public static function getAllPosts(): array
    {
        return array_values(self::$posts);
    }

    public static function getPost(int $id): ?array
    {
        return self::$posts[$id] ?? null;
    }

    public static function getUserPosts(int $userId): array
    {
        return array_values(array_filter(self::$posts, fn($post) => $post['user_id'] === $userId));
    }

    public static function createPost(array $data): array
    {
        $post = [
            'id' => self::$nextPostId++,
            'title' => $data['title'] ?? 'Untitled',
            'content' => $data['content'] ?? '',
            'user_id' => $data['user_id'] ?? 1,
            'created_at' => date('Y-m-d H:i:s')
        ];

        self::$posts[$post['id']] = $post;
        return $post;
    }

    public static function deletePost(int $id): bool
    {
        if (isset(self::$posts[$id])) {
            unset(self::$posts[$id]);
            return true;
        }
        return false;
    }
}

// REST API контроллеры
class ApiController
{
    public function documentation(): Response
    {
        $docs = [
            'title' => 'REST API Documentation',
            'version' => '1.0',
            'description' => 'Full REST API implementation with CRUD operations',
            'endpoints' => [
                'users' => [
                    'GET /api/users' => 'List all users',
                    'POST /api/users' => 'Create new user',
                    'GET /api/users/{id}' => 'Get specific user',
                    'PUT /api/users/{id}' => 'Update user',
                    'DELETE /api/users/{id}' => 'Delete user',
                    'GET /api/users/{id}/posts' => "Get user's posts"
                ],
                'posts' => [
                    'GET /api/posts' => 'List all posts',
                    'POST /api/posts' => 'Create new post',
                    'GET /api/posts/{id}' => 'Get specific post',
                    'DELETE /api/posts/{id}' => 'Delete post'
                ]
            ],
            'features' => [
                'JSON responses',
                'Proper HTTP status codes',
                'Error handling',
                'Request validation',
                'Resource relationships'
            ],
            'generated_at' => date('c')
        ];

        return Response::json($docs);
    }
}

class UsersController
{
    public function index(): Response
    {
        $users = Database::getAllUsers();

        return Response::json([
            'data' => $users,
            'count' => count($users),
            'timestamp' => date('c')
        ]);
    }

    public function show($id): Response
    {
        $userId = (int) $id;
        $user = Database::getUser($userId);

        if (!$user) {
            return Response::json([
                'error' => 'User not found',
                'message' => "User with ID $userId does not exist"
            ], 404);
        }

        return Response::json(['data' => $user]);
    }

    public function store(Request $request): Response
    {
        // Симуляция получения JSON данных
        $data = [
            'name' => 'New User #' . rand(1000, 9999),
            'email' => 'newuser' . rand(100, 999) . '@example.com',
            'role' => 'user'
        ];

        // Валидация
        if (empty($data['name']) || empty($data['email'])) {
            return Response::json([
                'error' => 'Validation failed',
                'message' => 'Name and email are required'
            ], 400);
        }

        $user = Database::createUser($data);

        return Response::json([
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    public function update(Request $request, $id): Response
    {
        $userId = (int) $id;

        // Симуляция обновления данных
        $data = [
            'name' => 'Updated User #' . $userId,
            'email' => "updated$userId@example.com"
        ];

        $user = Database::updateUser($userId, $data);

        if (!$user) {
            return Response::json([
                'error' => 'User not found',
                'message' => "Cannot update user with ID $userId"
            ], 404);
        }

        return Response::json([
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    public function destroy($id): Response
    {
        $userId = (int) $id;
        $deleted = Database::deleteUser($userId);

        if (!$deleted) {
            return Response::json([
                'error' => 'User not found',
                'message' => "User with ID $userId does not exist"
            ], 404);
        }

        return Response::json([
            'message' => "User $userId deleted successfully"
        ]);
    }

    public function posts($id): Response
    {
        $userId = (int) $id;
        $user = Database::getUser($userId);

        if (!$user) {
            return Response::json([
                'error' => 'User not found',
                'message' => "User with ID $userId does not exist"
            ], 404);
        }

        $posts = Database::getUserPosts($userId);

        return Response::json([
            'user' => $user,
            'posts' => $posts,
            'count' => count($posts)
        ]);
    }
}

class PostsController
{
    public function index(): Response
    {
        $posts = Database::getAllPosts();

        return Response::json([
            'data' => $posts,
            'count' => count($posts),
            'timestamp' => date('c')
        ]);
    }

    public function show($id): Response
    {
        $postId = (int) $id;
        $post = Database::getPost($postId);

        if (!$post) {
            return Response::json([
                'error' => 'Post not found',
                'message' => "Post with ID $postId does not exist"
            ], 404);
        }

        // Получаем информацию об авторе
        $author = Database::getUser($post['user_id']);
        $post['author'] = $author;

        return Response::json(['data' => $post]);
    }

    public function store(Request $request): Response
    {
        // Симуляция создания поста
        $data = [
            'title' => 'New Post #' . rand(1000, 9999),
            'content' => 'This is a sample post content created via API.',
            'user_id' => rand(1, 3)
        ];

        $post = Database::createPost($data);

        return Response::json([
            'message' => 'Post created successfully',
            'data' => $post
        ], 201);
    }

    public function destroy($id): Response
    {
        $postId = (int) $id;
        $deleted = Database::deletePost($postId);

        if (!$deleted) {
            return Response::json([
                'error' => 'Post not found',
                'message' => "Post with ID $postId does not exist"
            ], 404);
        }

        return Response::json([
            'message' => "Post $postId deleted successfully"
        ]);
    }
}

// Создаем коллекцию маршрутов
$routes = new RoutesCollection();

// === API Documentation ===
$routes->set(Route::create('/api', ApiController::class, 'documentation', [], ['GET']));

// === Users CRUD ===
$routes->set(Route::create('/api/users', UsersController::class, 'index', [], ['GET']));
$routes->set(Route::create('/api/users', UsersController::class, 'store', [], ['POST']));
$routes->set(Route::create('/api/users/{id}', UsersController::class, 'show', [], ['GET']));
$routes->set(Route::create('/api/users/{id}', UsersController::class, 'update', [], ['PUT']));
$routes->set(Route::create('/api/users/{id}', UsersController::class, 'destroy', [], ['DELETE']));

// === User Posts ===
$routes->set(Route::create('/api/users/{id}/posts', UsersController::class, 'posts', [], ['GET']));

// === Posts CRUD ===
$routes->set(Route::create('/api/posts', PostsController::class, 'index', [], ['GET']));
$routes->set(Route::create('/api/posts', PostsController::class, 'store', [], ['POST']));
$routes->set(Route::create('/api/posts/{id}', PostsController::class, 'show', [], ['GET']));
$routes->set(Route::create('/api/posts/{id}', PostsController::class, 'destroy', [], ['DELETE']));

// Создаем роутер и запускаем
$router = new Router();

try {
    echo "=== REST API Example ===\n\n";

    echo "Full REST API implementation with CRUD operations:\n\n";

    echo "📋 API Information:\n";
    echo "   GET /api - API documentation\n\n";

    echo "👤 User Management:\n";
    echo "   GET    /api/users       - List all users\n";
    echo "   POST   /api/users       - Create new user\n";
    echo "   GET    /api/users/{id}  - Get specific user\n";
    echo "   PUT    /api/users/{id}  - Update user\n";
    echo "   DELETE /api/users/{id}  - Delete user\n";
    echo "   GET    /api/users/{id}/posts - Get user's posts\n\n";

    echo "📝 Post Management:\n";
    echo "   GET    /api/posts       - List all posts\n";
    echo "   POST   /api/posts       - Create new post\n";
    echo "   GET    /api/posts/{id}  - Get specific post\n";
    echo "   DELETE /api/posts/{id}  - Delete post\n\n";

    echo "🔧 Features:\n";
    echo "   ✓ Full CRUD operations\n";
    echo "   ✓ JSON responses with proper status codes\n";
    echo "   ✓ Error handling (404, 400, 500)\n";
    echo "   ✓ Request/Response objects\n";
    echo "   ✓ Resource relationships (user posts)\n";
    echo "   ✓ Validation and error messages\n";
    echo "   ✓ Timestamps in responses\n\n";

    echo "🧪 Test commands:\n";
    echo "REQUEST_URI=\"/api\" php rest-api-example.php\n";
    echo "REQUEST_URI=\"/api/users\" php rest-api-example.php\n";
    echo "REQUEST_URI=\"/api/users/1\" php rest-api-example.php\n";
    echo "REQUEST_URI=\"/api/users/1/posts\" php rest-api-example.php\n";
    echo "REQUEST_URI=\"/api/posts\" php rest-api-example.php\n";
    echo "REQUEST_URI=\"/api/posts/1\" php rest-api-example.php\n\n";

    echo "# Test different HTTP methods:\n";
    echo "REQUEST_METHOD=\"POST\" REQUEST_URI=\"/api/users\" php rest-api-example.php\n";
    echo "REQUEST_METHOD=\"PUT\" REQUEST_URI=\"/api/users/1\" php rest-api-example.php\n";
    echo "REQUEST_METHOD=\"DELETE\" REQUEST_URI=\"/api/users/999\" php rest-api-example.php\n\n";

    echo "============================================================\n\n";

    $router->setCollection($routes)->run();

    echo "\n\n=== REST API Example Complete ===\n";
} catch (Exception $e) {
    echo "\n❌ API Error: " . $e->getMessage() . "\n";
    echo "Please check the endpoint and HTTP method.\n";
}
