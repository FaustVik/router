<?php

declare(strict_types=1);

namespace FaustVik\Router\Cache;

use FaustVik\Router\interfaces\Cache\CacheInterface;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function is_dir;
use function is_file;
use function mkdir;
use function serialize;
use function time;
use function unlink;
use function unserialize;

final class FileCache implements CacheInterface
{
    private string $cacheDir;
    private string $prefix;

    public function __construct(string $cacheDir = 'cache', string $prefix = 'router_')
    {
        $this->cacheDir = rtrim($cacheDir, '/');
        $this->prefix = $prefix;

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

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

        $data = unserialize($content);

        // Проверяем TTL
        if ($data['ttl'] > 0 && time() > $data['ttl']) {
            $this->delete($key);
            return null;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        $filename = $this->getFilename($key);

        $data = [
            'value' => $value,
            'ttl' => $ttl > 0 ? time() + $ttl : 0,
            'created' => time()
        ];

        $serialized = serialize($data);

        return file_put_contents($filename, $serialized, LOCK_EX) !== false;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): bool
    {
        $filename = $this->getFilename($key);

        if (file_exists($filename)) {
            return unlink($filename);
        }

        return true;
    }

    public function clear(): bool
    {
        $pattern = $this->cacheDir . '/' . $this->prefix . '*';
        $files = glob($pattern);

        if ($files === false) {
            return true;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    public function getMultiple(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

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

    private function getFilename(string $key): string
    {
        return $this->cacheDir . '/' . $this->prefix . md5($key) . '.cache';
    }
}
