<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Cache;

use FaustVik\Router\Cache\FileCache;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Тесты для FileCache
 *
 * Проверяет:
 * - Базовую функциональность (set/get/delete/clear)
 * - Безопасность (Path Traversal, JSON serialization)
 * - TTL и истечение срока
 * - Множественные операции
 * - Защиту директории кеша
 */
final class FileCacheTest extends TestCase
{
    private string $testCacheDir;
    private FileCache $cache;

    protected function setUp(): void
    {
        // Создаем уникальную директорию для каждого теста
        $this->testCacheDir = sys_get_temp_dir() . '/router_test_cache_' . uniqid();
        $this->cache = new FileCache($this->testCacheDir, 'test_');
    }

    protected function tearDown(): void
    {
        // Очищаем тестовую директорию
        if (is_dir($this->testCacheDir)) {
            $this->recursiveRemoveDirectory($this->testCacheDir);
        }
    }

    private function recursiveRemoveDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->recursiveRemoveDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    // ========================================================================
    // Базовая функциональность
    // ========================================================================

    public function testSetAndGet(): void
    {
        $result = $this->cache->set('test_key', 'test_value');
        $this->assertTrue($result);

        $value = $this->cache->get('test_key');
        $this->assertEquals('test_value', $value);
    }

    public function testGetNonExistentKey(): void
    {
        $value = $this->cache->get('non_existent_key');
        $this->assertNull($value);
    }

    public function testSetAndGetDifferentDataTypes(): void
    {
        // String
        $this->cache->set('string_key', 'hello world');
        $this->assertEquals('hello world', $this->cache->get('string_key'));

        // Integer
        $this->cache->set('int_key', 42);
        $this->assertSame(42, $this->cache->get('int_key'));

        // Float
        $this->cache->set('float_key', 3.14);
        $this->assertSame(3.14, $this->cache->get('float_key'));

        // Boolean
        $this->cache->set('bool_true', true);
        $this->assertTrue($this->cache->get('bool_true'));

        $this->cache->set('bool_false', false);
        $this->assertFalse($this->cache->get('bool_false'));

        // Array
        $array = ['foo' => 'bar', 'nested' => ['key' => 'value']];
        $this->cache->set('array_key', $array);
        $this->assertEquals($array, $this->cache->get('array_key'));

        // Null
        $this->cache->set('null_key', null);
        $this->assertNull($this->cache->get('null_key'));
    }

    public function testHas(): void
    {
        $this->assertFalse($this->cache->has('test_key'));

        $this->cache->set('test_key', 'test_value');
        $this->assertTrue($this->cache->has('test_key'));
    }

    public function testDelete(): void
    {
        $this->cache->set('test_key', 'test_value');
        $this->assertTrue($this->cache->has('test_key'));

        $result = $this->cache->delete('test_key');
        $this->assertTrue($result);
        $this->assertFalse($this->cache->has('test_key'));
    }

    public function testDeleteNonExistentKey(): void
    {
        $result = $this->cache->delete('non_existent_key');
        $this->assertTrue($result); // Должно вернуть true даже если ключа нет
    }

    public function testClear(): void
    {
        // Создаем несколько ключей
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        $this->assertTrue($this->cache->has('key1'));
        $this->assertTrue($this->cache->has('key2'));
        $this->assertTrue($this->cache->has('key3'));

        // Очищаем кеш
        $result = $this->cache->clear();
        $this->assertTrue($result);

        // Проверяем что все ключи удалены
        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
        $this->assertFalse($this->cache->has('key3'));
    }

    public function testOverwriteExistingKey(): void
    {
        $this->cache->set('test_key', 'old_value');
        $this->assertEquals('old_value', $this->cache->get('test_key'));

        $this->cache->set('test_key', 'new_value');
        $this->assertEquals('new_value', $this->cache->get('test_key'));
    }

    // ========================================================================
    // TTL (Time To Live) тесты
    // ========================================================================

    public function testTtlExpiration(): void
    {
        // Устанавливаем TTL 1 секунду
        $this->cache->set('expiring_key', 'test_value', 1);

        // Сразу должно быть доступно
        $this->assertEquals('test_value', $this->cache->get('expiring_key'));
        $this->assertTrue($this->cache->has('expiring_key'));

        // Ждем 2 секунды
        sleep(2);

        // Теперь должно быть истекшим
        $this->assertNull($this->cache->get('expiring_key'));
        $this->assertFalse($this->cache->has('expiring_key'));
    }

    public function testTtlZeroMeansNoExpiration(): void
    {
        // TTL = 0 означает что кеш не истекает
        $this->cache->set('permanent_key', 'test_value', 0);

        sleep(1);

        $this->assertEquals('test_value', $this->cache->get('permanent_key'));
        $this->assertTrue($this->cache->has('permanent_key'));
    }

    public function testTtlDefaultIsZero(): void
    {
        // Без указания TTL кеш не должен истекать
        $this->cache->set('default_ttl_key', 'test_value');

        sleep(1);

        $this->assertEquals('test_value', $this->cache->get('default_ttl_key'));
    }

    // ========================================================================
    // Множественные операции
    // ========================================================================

    public function testGetMultiple(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        $result = $this->cache->getMultiple(['key1', 'key2', 'key3', 'non_existent']);

        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'non_existent' => null,
        ], $result);
    }

    public function testSetMultiple(): void
    {
        $values = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        $result = $this->cache->setMultiple($values);
        $this->assertTrue($result);

        $this->assertEquals('value1', $this->cache->get('key1'));
        $this->assertEquals('value2', $this->cache->get('key2'));
        $this->assertEquals('value3', $this->cache->get('key3'));
    }

    public function testSetMultipleWithTtl(): void
    {
        $values = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $this->cache->setMultiple($values, 1);

        $this->assertTrue($this->cache->has('key1'));
        $this->assertTrue($this->cache->has('key2'));

        sleep(2);

        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
    }

    public function testDeleteMultiple(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        $result = $this->cache->deleteMultiple(['key1', 'key3']);
        $this->assertTrue($result);

        $this->assertFalse($this->cache->has('key1'));
        $this->assertTrue($this->cache->has('key2'));
        $this->assertFalse($this->cache->has('key3'));
    }

    // ========================================================================
    // БЕЗОПАСНОСТЬ - Критичные тесты!
    // ========================================================================

    public function testPathTraversalProtection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('path traversal detected');

        // Попытка создать кеш с path traversal
        new FileCache('../../../etc', 'evil_');
    }

    public function testPathTraversalInKeyDoesNotBreakSecurity(): void
    {
        // Попытка использовать path traversal в ключе
        // FileCache использует md5 для имени файла, так что это безопасно
        $maliciousKey = '../../../etc/passwd';

        $this->cache->set($maliciousKey, 'test_value');
        $value = $this->cache->get($maliciousKey);

        $this->assertEquals('test_value', $value);

        // Проверяем что файл создан в правильной директории (нормализуем пути для macOS)
        $expectedDir = realpath($this->testCacheDir) ?: $this->testCacheDir;
        $actualDir = $this->cache->getCacheDir();
        $this->assertEquals($expectedDir, $actualDir);
    }

    public function testJsonSerializationInsteadOfUnserialize(): void
    {
        // Проверяем что используется JSON, не serialize
        // Это защищает от PHP Object Injection атак

        $this->cache->set('test_key', ['foo' => 'bar']);

        // Читаем файл напрямую
        $files = glob($this->testCacheDir . '/test_*.cache');
        $this->assertNotEmpty($files);

        $content = file_get_contents($files[0]);

        // Проверяем что это JSON, а не serialized PHP
        $this->assertStringStartsWith('{', $content);
        $this->assertStringNotContainsString('O:', $content); // serialized object начинается с O:

        // Проверяем что это валидный JSON
        $decoded = json_decode($content, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('value', $decoded);
        $this->assertArrayHasKey('ttl', $decoded);
        $this->assertArrayHasKey('created', $decoded);
    }

    public function testCorruptedCacheFileIsDeletedAndReturnsNull(): void
    {
        // Создаем валидную запись
        $this->cache->set('test_key', 'test_value');

        // Портим файл кеша
        $files = glob($this->testCacheDir . '/test_*.cache');
        $this->assertNotEmpty($files);
        file_put_contents($files[0], 'corrupted data');

        // get() должен вернуть null и удалить поврежденный файл
        $value = $this->cache->get('test_key');
        $this->assertNull($value);

        // Файл должен быть удален
        clearstatcache();
        $this->assertFileDoesNotExist($files[0]);
    }

    public function testInvalidCacheStructureIsDeletedAndReturnsNull(): void
    {
        // Создаем файл с невалидной структурой данных
        $this->cache->set('test_key', 'test_value');

        $files = glob($this->testCacheDir . '/test_*.cache');
        $this->assertNotEmpty($files);

        // Записываем JSON без необходимых полей
        file_put_contents($files[0], json_encode(['invalid' => 'structure']));

        $value = $this->cache->get('test_key');
        $this->assertNull($value);

        clearstatcache();
        $this->assertFileDoesNotExist($files[0]);
    }

    // ========================================================================
    // Защита директории кеша
    // ========================================================================

    public function testCacheDirectoryProtectionFilesCreated(): void
    {
        // .gitignore должен быть создан
        $this->assertFileExists($this->testCacheDir . '/.gitignore');

        $gitignoreContent = file_get_contents($this->testCacheDir . '/.gitignore');
        $this->assertStringContainsString('*', $gitignoreContent);
        $this->assertStringContainsString('!.gitignore', $gitignoreContent);

        // .htaccess должен быть создан
        $this->assertFileExists($this->testCacheDir . '/.htaccess');

        $htaccessContent = file_get_contents($this->testCacheDir . '/.htaccess');
        $this->assertStringContainsString('Deny from all', $htaccessContent);

        // index.php должен быть создан
        $this->assertFileExists($this->testCacheDir . '/index.php');

        $indexContent = file_get_contents($this->testCacheDir . '/index.php');
        $this->assertStringContainsString('403', $indexContent);
        $this->assertStringContainsString('Access denied', $indexContent);
    }

    public function testCacheDirectoryIsCreatedIfNotExists(): void
    {
        $newCacheDir = $this->testCacheDir . '_new';

        $this->assertDirectoryDoesNotExist($newCacheDir);

        $cache = new FileCache($newCacheDir);

        $this->assertDirectoryExists($newCacheDir);

        // Нормализуем пути для сравнения (macOS использует /private/var)
        $expectedDir = realpath($newCacheDir) ?: $newCacheDir;
        $actualDir = $cache->getCacheDir();
        $this->assertEquals($expectedDir, $actualDir);

        // Очистка
        $this->recursiveRemoveDirectory($newCacheDir);
    }

    public function testNonWritableCacheDirectoryThrowsException(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Test skipped on Windows (chmod behaves differently)');
        }

        $nonWritableDir = $this->testCacheDir . '_readonly';
        mkdir($nonWritableDir, 0444);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('not writable');

            new FileCache($nonWritableDir);
        } finally {
            chmod($nonWritableDir, 0755);
            rmdir($nonWritableDir);
        }
    }

    // ========================================================================
    // Дополнительные тесты
    // ========================================================================

    public function testGetCacheDir(): void
    {
        // Нормализуем пути для сравнения (macOS использует /private/var)
        $expectedDir = realpath($this->testCacheDir) ?: $this->testCacheDir;
        $actualDir = $this->cache->getCacheDir();
        $this->assertEquals($expectedDir, $actualDir);
    }

    public function testGetPrefix(): void
    {
        $this->assertEquals('test_', $this->cache->getPrefix());
    }

    public function testCustomPrefix(): void
    {
        $cache = new FileCache($this->testCacheDir, 'custom_prefix_');

        $this->assertEquals('custom_prefix_', $cache->getPrefix());

        $cache->set('test', 'value');

        $files = glob($this->testCacheDir . '/custom_prefix_*.cache');
        $this->assertNotEmpty($files);
    }

    public function testMultipleCacheInstancesWithDifferentPrefixes(): void
    {
        $cache1 = new FileCache($this->testCacheDir, 'cache1_');
        $cache2 = new FileCache($this->testCacheDir, 'cache2_');

        $cache1->set('shared_key', 'value1');
        $cache2->set('shared_key', 'value2');

        // Оба кеша используют одну директорию но разные префиксы
        $this->assertEquals('value1', $cache1->get('shared_key'));
        $this->assertEquals('value2', $cache2->get('shared_key'));

        // clear() очищает только файлы с соответствующим префиксом
        $cache1->clear();

        $this->assertNull($cache1->get('shared_key'));
        $this->assertEquals('value2', $cache2->get('shared_key'));
    }

    public function testEmptyStringKey(): void
    {
        $this->cache->set('', 'empty_key_value');
        $this->assertEquals('empty_key_value', $this->cache->get(''));
    }

    public function testVeryLongKey(): void
    {
        $longKey = str_repeat('a', 1000);

        $this->cache->set($longKey, 'long_key_value');
        $this->assertEquals('long_key_value', $this->cache->get($longKey));
    }

    public function testSpecialCharactersInKey(): void
    {
        $specialKeys = [
            'key with spaces',
            'key/with/slashes',
            'key:with:colons',
            'key@with@at',
            'ключ_на_русском',
            'キー日本語',
            'مفتاح_عربي',
        ];

        foreach ($specialKeys as $key) {
            $this->cache->set($key, "value_for_{$key}");
            $this->assertEquals("value_for_{$key}", $this->cache->get($key));
        }
    }

    public function testConcurrentWritesWithLockEx(): void
    {
        // Этот тест проверяет что LOCK_EX предотвращает race conditions
        // В реальных условиях нужно больше процессов, но это базовая проверка

        $this->cache->set('counter', 0);

        // Множественные записи
        for ($i = 0; $i < 10; $i++) {
            $value = $this->cache->get('counter');
            $this->cache->set('counter', $value + 1);
        }

        $finalValue = $this->cache->get('counter');
        $this->assertEquals(10, $finalValue);
    }
}
