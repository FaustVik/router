<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use DI\ContainerBuilder;
use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\Route\RoutesCollection;
use FaustVik\Router\Router\Router;
use FaustVik\Router\Validation\ParameterValidationRule;

// Интерфейс для демонстрации
interface DatabaseInterface
{
    public function query(string $sql): array;
}

// Реализация базы данных
class Database implements DatabaseInterface
{
    private string $dsn;

    public function __construct(string $dsn)
    {
        $this->dsn = $dsn;
    }

    public function query(string $sql): array
    {
        echo "[DB] Executing query: {$sql}\n";
        return ['result' => 'success', 'rows' => 3];
    }
}

// Сервис для работы с продуктами
class ProductService
{
    private DatabaseInterface $database;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    public function getProduct(int $id): array
    {
        $this->database->query("SELECT * FROM products WHERE id = {$id}");
        return [
            'id' => $id,
            'name' => 'Product ' . $id,
            'price' => 99.99
        ];
    }

    public function getAllProducts(): array
    {
        $this->database->query("SELECT * FROM products");
        return [
            ['id' => 1, 'name' => 'Product 1', 'price' => 99.99],
            ['id' => 2, 'name' => 'Product 2', 'price' => 149.99],
        ];
    }
}

// Контроллер для продуктов
class ProductController
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(): void
    {
        $products = $this->productService->getAllProducts();
        
        echo json_encode([
            'status' => 'success',
            'data' => $products
        ]);
    }

    public function show(int $id): void
    {
        $product = $this->productService->getProduct($id);
        
        echo json_encode([
            'status' => 'success',
            'data' => $product
        ]);
    }
}

// Создание и настройка внешнего PHP-DI контейнера
$containerBuilder = new ContainerBuilder();

// Настройка контейнера с помощью конфигурационного массива
$containerBuilder->addDefinitions([
    DatabaseInterface::class => function() {
        return new Database('mysql:host=localhost;dbname=myapp');
    },
    ProductService::class => function(DatabaseInterface $database) {
        return new ProductService($database);
    },
    // Автоматическая настройка контроллера через autowiring
    ProductController::class => \DI\autowire(),
]);

// Создание контейнера
$container = $containerBuilder->build();

// Создание роутера и установка внешнего контейнера
$router = new Router();
$router->setContainer($container);

// Альтернативный способ - создание адаптера вручную
// $adapter = new \FaustVik\Router\DI\Adapters\PhpDiContainerAdapter($container);
// $router->setContainer($adapter);

// Создание коллекции маршрутов
$routes = new RoutesCollection();

// Добавляем маршруты - контроллеры будут созданы через внешний контейнер
$routes->addGet('/products', ProductController::class, 'index');
$routes->addGet('/products/{id}', ProductController::class, 'show')
    ->validate([
        ParameterValidationRule::for('id')->regex('/^\d+$/')
    ]);

// Настройка роутера
$router->setCollection($routes);

// Демонстрация работы
echo "=== Демонстрация внешнего PHP-DI контейнера ===\n\n";

// Тестирование разных маршрутов
echo "1. GET /products:\n";
$router->setUri('/products');
$_SERVER['REQUEST_METHOD'] = 'GET';
$router->run();

echo "\n\n2. GET /products/42:\n";
$router->setUri('/products/42');
$_SERVER['REQUEST_METHOD'] = 'GET';
$router->run();

echo "\n\n=== Информация о контейнере ===\n";
echo "DI включен: " . ($router->isDIEnabled() ? 'Да' : 'Нет') . "\n";
echo "Тип контейнера: " . get_class($router->getContainer()) . "\n";
echo "Может создать ProductController: " . ($router->getContainer()->canResolve(ProductController::class) ? 'Да' : 'Нет') . "\n";

// Проверка типов контейнеров
echo "\n=== Поддерживаемые типы контейнеров ===\n";
$supportedTypes = \FaustVik\Router\DI\ContainerAdapterFactory::getSupportedTypes();
foreach ($supportedTypes as $type => $adapterClass) {
    echo "- {$type}: {$adapterClass}\n";
} 