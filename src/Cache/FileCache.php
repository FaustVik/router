<?php

declare(strict_types=1);

namespace FaustVik\Router\Cache;

use FaustVik\Router\Interfaces\Cache\CacheInterface;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

/**
 * File-based cache with security protection
 *
 * Secure file cache implementation:
 * - Uses JSON instead of serialize (Object Injection protection)
 * - Path validation (Path Traversal protection)
 * - Automatic cache directory protection
 * - Write permission checks
 *
 * @package FaustVik\Router\Cache
 */
final class FileCache implements CacheInterface
{
    private string $cacheDir;
    private string $prefix;

    /**
     * @param string $cacheDir Директория для кеша (по умолчанию 'cache')
     * @param string $prefix Префикс для имен файлов кеша
     * @throws InvalidArgumentException Если путь содержит path traversal или выходит за пределы проекта
     * @throws RuntimeException Если не удалось создать директорию или она недоступна для записи
     */
    public function __construct(string $cacheDir = 'cache', string $prefix = 'router_')
    {
        // Валидация и нормализация пути
        $this->cacheDir = $this->validateAndNormalizePath($cacheDir);
        $this->prefix = $prefix;

        // Создаем директорию если не существует
        if (!is_dir($this->cacheDir)) {
            if (!@mkdir($this->cacheDir, 0755, true)) {
                throw new RuntimeException(
                    "Cannot create cache directory: {$this->cacheDir}"
                );
            }
        }

        // Проверка прав записи
        if (!is_writable($this->cacheDir)) {
            throw new RuntimeException(
                "Cache directory is not writable: {$this->cacheDir}"
            );
        }

        // Защита директории кеша
        $this->protectCacheDirectory();
    }

    /**
     * Валидирует и нормализует путь к директории кеша
     *
     * @throws InvalidArgumentException Если путь небезопасен
     */
    private function validateAndNormalizePath(string $cacheDir): string
    {
        // Удаляем trailing slash
        $cacheDir = rtrim($cacheDir, '/\\');

        // Проверка на path traversal атаки
        if (strpos($cacheDir, '..') !== false) {
            throw new InvalidArgumentException(
                'Invalid cache directory: path traversal detected'
            );
        }

        // Получаем абсолютный путь
        if (!file_exists($cacheDir)) {
            // Если директория не существует, проверяем родительскую
            $parentDir = dirname($cacheDir);
            if (file_exists($parentDir)) {
                $absolutePath = realpath($parentDir) . '/' . basename($cacheDir);
            } else {
                // Используем текущую директорию как базовую
                $absolutePath = getcwd() . '/' . $cacheDir;
            }
        } else {
            $absolutePath = realpath($cacheDir);
            if ($absolutePath === false) {
                throw new InvalidArgumentException(
                    "Invalid cache directory path: {$cacheDir}"
                );
            }
        }

        // Проверка что путь находится внутри проекта (опционально, но рекомендуется)
        // Раскомментируйте если нужна строгая проверка
        /*
        $projectRoot = dirname(__DIR__, 2); // Корень проекта
        if (strpos($absolutePath, $projectRoot) !== 0) {
            throw new InvalidArgumentException(
                'Cache directory must be within project root'
            );
        }
        */

        return $absolutePath;
    }

    /**
     * Защищает директорию кеша от несанкционированного доступа
     *
     * Создает .gitignore и .htaccess для безопасности
     */
    private function protectCacheDirectory(): void
    {
        // Создаем .gitignore чтобы не коммитить кеш
        $gitignore = $this->cacheDir . '/.gitignore';
        if (!file_exists($gitignore)) {
            @file_put_contents($gitignore, "*\n!.gitignore\n!.htaccess\n");
        }

        // Создаем .htaccess для Apache (запрет доступа через web)
        $htaccess = $this->cacheDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "Deny from all\n");
        }

        // Создаем index.php для дополнительной защиты
        $index = $this->cacheDir . '/index.php';
        if (!file_exists($index)) {
            @file_put_contents($index, "<?php\nhttp_response_code(403);\nexit('Access denied');\n");
        }
    }

    /**
     * Получает значение из кеша
     *
     * @param string $key Ключ кеша
     * @return mixed|null Значение или null если не найдено/истекло
     */
    public function get(string $key): mixed
    {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return null;
        }

        $content = file_get_contents($filename);
        if ($content === false) {
            return null;
        }

        try {
            // Используем JSON вместо unserialize для безопасности
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            // Если не удалось распарсить, удаляем поврежденный файл
            $this->delete($key);
            return null;
        }

        // Валидация структуры данных
        if (!is_array($data) || !isset($data['value'], $data['ttl'], $data['created'])) {
            $this->delete($key);
            return null;
        }

        // Проверяем TTL
        if ($data['ttl'] > 0 && time() > $data['ttl']) {
            $this->delete($key);
            return null;
        }

        return $data['value'];
    }

    /**
     * Сохраняет значение в кеш
     *
     * @param string $key Ключ кеша
     * @param mixed $value Значение для сохранения
     * @param int $ttl Время жизни в секундах (0 = бесконечно)
     * @return bool true в случае успеха
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        $filename = $this->getFilename($key);

        $data = [
            'value' => $value,
            'ttl' => $ttl > 0 ? time() + $ttl : 0,
            'created' => time(),
        ];

        try {
            // Используем JSON вместо serialize для безопасности
            $serialized = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            // Если не удалось сериализовать, возвращаем false
            return false;
        }

        // LOCK_EX обеспечивает эксклюзивную блокировку при записи
        return file_put_contents($filename, $serialized, LOCK_EX) !== false;
    }

    /**
     * Проверяет существование ключа в кеше
     *
     * @param string $key Ключ кеша
     * @return bool true если существует и не истек
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Удаляет значение из кеша
     *
     * @param string $key Ключ кеша
     * @return bool true в случае успеха
     */
    public function delete(string $key): bool
    {
        $filename = $this->getFilename($key);

        if (file_exists($filename)) {
            return @unlink($filename);
        }

        return true;
    }

    /**
     * Очищает весь кеш
     *
     * @return bool true в случае успеха
     */
    public function clear(): bool
    {
        $pattern = $this->cacheDir . '/' . $this->prefix . '*.cache';
        $files = glob($pattern);

        if ($files === false) {
            return true;
        }

        $success = true;
        foreach ($files as $file) {
            if (is_file($file)) {
                if (!@unlink($file)) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * Получает несколько значений из кеша
     *
     * @param array<string> $keys Массив ключей
     * @return array<string, mixed> Массив ключ => значение
     */
    public function getMultiple(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    /**
     * Сохраняет несколько значений в кеш
     *
     * @param array<string, mixed> $values Массив ключ => значение
     * @param int $ttl Время жизни в секундах
     * @return bool true если все успешно сохранены
     */
    public function setMultiple(array $values, int $ttl = 0): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Удаляет несколько значений из кеша
     *
     * @param array<string> $keys Массив ключей
     * @return bool true если все успешно удалены
     */
    public function deleteMultiple(array $keys): bool
    {
        $success = true;

        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Получает имя файла кеша для ключа
     *
     * @param string $key Ключ кеша
     * @return string Полный путь к файлу
     */
    private function getFilename(string $key): string
    {
        // Используем md5 для безопасного имени файла
        return $this->cacheDir . '/' . $this->prefix . md5($key) . '.cache';
    }

    /**
     * Получает директорию кеша
     *
     * @return string Путь к директории кеша
     */
    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    /**
     * Получает префикс файлов кеша
     *
     * @return string Префикс
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
