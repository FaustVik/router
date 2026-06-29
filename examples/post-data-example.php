<?php

declare(strict_types=1);

/**
 * Пример работы с POST/PUT данными и JSON body
 *
 * Демонстрирует:
 * - Обработку JSON запросов (Content-Type: application/json)
 * - Обработку form-data запросов
 * - Загрузку файлов
 * - Работу с PUT/PATCH/DELETE данными
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Router\Router;

$router = new Router();

// ============================================
// 1. Пример: Создание пользователя (JSON body)
// ============================================
// POST /api/users
// Content-Type: application/json
// Body: {"name": "John Doe", "email": "john@example.com", "age": 30}
$router->post('/api/users', function (Request $request) {
    // Получаем все данные из body
    $data = $request->getBody();

    // Или получаем отдельные поля
    $name = $request->input('name');
    $email = $request->input('email');
    $age = $request->input('age', 18); // со значением по умолчанию

    // Проверяем наличие полей
    if (!$request->has('name')) {
        return Response::json([
            'error' => 'Name is required',
        ], 400);
    }

    // Проверяем является ли запрос JSON
    if (!$request->isJson()) {
        return Response::json([
            'error' => 'Content-Type must be application/json',
        ], 415);
    }

    return Response::json([
        'message' => 'User created successfully',
        'user' => [
            'name' => $name,
            'email' => $email,
            'age' => $age,
        ],
    ], 201);
});

// ============================================
// 2. Пример: Обновление пользователя (PUT)
// ============================================
// PUT /api/users/123
// Content-Type: application/json
// Body: {"name": "Jane Doe", "age": 25}
$router->put('/api/users/{id}', function (Request $request) {
    $userId = $request->getParam('id');

    // PUT данные автоматически читаются из php://input
    $name = $request->input('name');
    $age = $request->input('age');

    return Response::json([
        'message' => 'User updated successfully',
        'user_id' => $userId,
        'updated_fields' => [
            'name' => $name,
            'age' => $age,
        ],
    ]);
});

// ============================================
// 3. Пример: Загрузка файла (form-data)
// ============================================
// POST /api/upload
// Content-Type: multipart/form-data
// Form fields: file (file), description (text)
$router->post('/api/upload', function (Request $request) {
    // Проверяем наличие файла
    if (!$request->hasFile('avatar')) {
        return Response::json([
            'error' => 'Avatar file is required',
        ], 400);
    }

    // Получаем данные файла
    $file = $request->file('avatar');

    // Получаем текстовые поля из form-data
    $description = $request->input('description', 'No description');

    // Информация о файле
    $fileInfo = [
        'name' => $file['name'],
        'type' => $file['type'],
        'size' => $file['size'],
        'tmp_name' => $file['tmp_name'],
        'error' => $file['error'],
    ];

    // В реальном приложении здесь была бы логика сохранения файла
    // move_uploaded_file($file['tmp_name'], '/path/to/destination/' . $file['name']);

    return Response::json([
        'message' => 'File uploaded successfully',
        'description' => $description,
        'file' => $fileInfo,
    ]);
});

// ============================================
// 4. Пример: Комбинированный API endpoint
// ============================================
$router->post('/api/posts', function (Request $request) {
    // Получаем все данные сразу
    $body = $request->getBody();

    // Валидация
    $required = ['title', 'content'];
    foreach ($required as $field) {
        if (!$request->has($field)) {
            return Response::json([
                'error' => "Field '{$field}' is required",
            ], 400);
        }
    }

    // Обработка опциональных полей
    $post = [
        'id' => uniqid(),
        'title' => $request->input('title'),
        'content' => $request->input('content'),
        'category' => $request->input('category', 'general'),
        'tags' => $request->input('tags', []),
        'published' => $request->input('published', false),
        'created_at' => date('Y-m-d H:i:s'),
    ];

    return Response::json([
        'message' => 'Post created successfully',
        'post' => $post,
    ], 201);
});

// ============================================
// 5. Пример: PATCH (частичное обновление)
// ============================================
$router->patch('/api/posts/{id}', function (Request $request) {
    $postId = $request->getParam('id');

    // PATCH обычно содержит только изменяемые поля
    $updates = $request->getBody();

    return Response::json([
        'message' => 'Post updated successfully',
        'post_id' => $postId,
        'updated_fields' => $updates,
    ]);
});

// ============================================
// 6. Пример: DELETE с body
// ============================================
$router->delete('/api/users/{id}', function (Request $request) {
    $userId = $request->getParam('id');

    // Некоторые API требуют причину удаления в body
    $reason = $request->input('reason', 'User requested');

    return Response::json([
        'message' => 'User deleted successfully',
        'user_id' => $userId,
        'reason' => $reason,
    ]);
});

// ============================================
// 7. Пример: Проверка типа запроса
// ============================================
$router->post('/api/data', function (Request $request) {
    $info = [
        'is_json' => $request->isJson(),
        'is_ajax' => $request->isAjax(),
        'method' => $request->getMethod(),
        'content_type' => $request->getHeader('Content-Type'),
        'body_data' => $request->getBody(),
    ];

    return Response::json($info);
});

// ============================================
// Запуск роутера
// ============================================
try {
    $router->run();
} catch (Exception $e) {
    Response::json([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
    ], 500)->send();
}

// ============================================
// Примеры использования через curl:
// ============================================

/*

# 1. POST JSON данные
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{"name": "John Doe", "email": "john@example.com", "age": 30}'

# 2. PUT обновление
curl -X PUT http://localhost:8000/api/users/123 \
  -H "Content-Type: application/json" \
  -d '{"name": "Jane Doe", "age": 25}'

# 3. POST с файлом
curl -X POST http://localhost:8000/api/upload \
  -F "avatar=@/path/to/image.jpg" \
  -F "description=My avatar"

# 4. POST создание поста
curl -X POST http://localhost:8000/api/posts \
  -H "Content-Type: application/json" \
  -d '{
    "title": "My First Post",
    "content": "This is the content",
    "category": "tech",
    "tags": ["php", "router"],
    "published": true
  }'

# 5. PATCH частичное обновление
curl -X PATCH http://localhost:8000/api/posts/123 \
  -H "Content-Type: application/json" \
  -d '{"title": "Updated Title"}'

# 6. DELETE с причиной
curl -X DELETE http://localhost:8000/api/users/123 \
  -H "Content-Type: application/json" \
  -d '{"reason": "Violated terms of service"}'

# 7. AJAX запрос
curl -X POST http://localhost:8000/api/data \
  -H "Content-Type: application/json" \
  -H "X-Requested-With: XMLHttpRequest" \
  -d '{"test": "data"}'

*/
