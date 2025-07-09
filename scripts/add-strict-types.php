<?php
declare(strict_types=1);

/**
 * Скрипт для добавления declare(strict_types=1) во все PHP файлы
 */

function addStrictTypesToFile(string $filePath): bool
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "Ошибка чтения файла: {$filePath}\n";
        return false;
    }

    // Проверяем, есть ли уже declare(strict_types=1)
    if (strpos($content, 'declare(strict_types=1)') !== false) {
        echo "Файл уже содержит declare(strict_types=1): {$filePath}\n";
        return true;
    }

    // Ищем <?php тег
    if (strpos($content, '<?php') !== 0) {
        echo "Файл не начинается с <?php: {$filePath}\n";
        return false;
    }

    // Добавляем declare(strict_types=1) после <?php
    $newContent = preg_replace(
        '/^<\?php\s*/',
        "<?php\ndeclare(strict_types=1);\n",
        $content
    );

    if ($newContent === null) {
        echo "Ошибка замены в файле: {$filePath}\n";
        return false;
    }

    // Сохраняем файл
    if (file_put_contents($filePath, $newContent) === false) {
        echo "Ошибка записи файла: {$filePath}\n";
        return false;
    }

    echo "✅ Добавлено declare(strict_types=1) в файл: {$filePath}\n";
    return true;
}

// Список файлов для обработки
$files = [
    'src/DI/ContainerAdapterFactory.php',
    'src/DI/DefaultContainer.php',
    'src/DI/Adapters/SymfonyContainerAdapter.php',
    'src/DI/Adapters/PhpDiContainerAdapter.php',
    'src/DI/Adapters/PimpleContainerAdapter.php',
    'src/exceptions/NotFoundClass.php',
    'src/exceptions/NoMatch.php',
    'src/exceptions/NotFoundMethod.php',
    'src/exceptions/NotAllowedHttpMethod.php',
    'src/exceptions/InvalidTypeRoute.php',
    'src/exceptions/Exception.php',
    'src/interfaces/Middleware/MiddlewareInterface.php',
    'src/interfaces/DI/RouterContainerInterface.php',
    'src/interfaces/Collections/RoutesCollectionInterface.php',
    'src/interfaces/Routes/RouteClassInterface.php',
    'src/interfaces/Routes/RouteAnonymousFuncInterface.php',
    'src/interfaces/Routes/RouteInterface.php',
    'src/interfaces/Router/Components/CheckHttpMethodInterface.php',
    'src/interfaces/Router/Components/ConfigInterface.php',
    'src/interfaces/Router/Components/RunnerInterface.php',
    'src/interfaces/Router/Components/MatchingRouteInterface.php',
    'src/interfaces/Router/RouterInterface.php',
    'examples/response-types-example.php',
    'examples/basic-example.php',
    'examples/di-external-container-example.php',
    'examples/di-middleware-example.php',
    'examples/url-parameters-example.php',
    'examples/di-basic-example.php',
    'examples/rest-api-example.php',
    'examples/error-handling-example.php',
];

$processedCount = 0;
$errorCount = 0;

echo "Начинаем обработку файлов...\n\n";

foreach ($files as $file) {
    if (file_exists($file)) {
        if (addStrictTypesToFile($file)) {
            $processedCount++;
        } else {
            $errorCount++;
        }
    } else {
        echo "❌ Файл не найден: {$file}\n";
        $errorCount++;
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "✅ Обработано файлов: {$processedCount}\n";
echo "❌ Ошибок: {$errorCount}\n";
echo "📁 Всего файлов: " . count($files) . "\n";

if ($errorCount === 0) {
    echo "\n🎉 Все файлы успешно обработаны!\n";
} else {
    echo "\n⚠️  Обработка завершена с ошибками!\n";
} 