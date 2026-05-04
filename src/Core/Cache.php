<?php

declare(strict_types=1);

namespace App\Core;

/**
 * File-based cache. Works on shared hosting without Redis.
 */
final class Cache
{
    public function __construct(private readonly string $dir)
    {
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->path($key);
        if (!is_file($file)) {
            return $default;
        }
        $payload = @file_get_contents($file);
        if ($payload === false) {
            return $default;
        }
        $data = @unserialize($payload);
        if (!is_array($data) || !isset($data['exp'], $data['val'])) {
            return $default;
        }
        if ($data['exp'] !== 0 && $data['exp'] < time()) {
            @unlink($file);
            return $default;
        }
        return $data['val'];
    }

    public function put(string $key, mixed $value, int $ttl = 0): bool
    {
        $payload = serialize(['exp' => $ttl > 0 ? time() + $ttl : 0, 'val' => $value]);
        return (bool) @file_put_contents($this->path($key), $payload, LOCK_EX);
    }

    public function forget(string $key): void
    {
        $file = $this->path($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public function flush(): void
    {
        foreach (glob($this->dir . '/*.cache') ?: [] as $file) {
            @unlink($file);
        }
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $hit = $this->get($key, $miss = '__cache_miss__');
        if ($hit !== '__cache_miss__') {
            return $hit;
        }
        $value = $callback();
        $this->put($key, $value, $ttl);
        return $value;
    }

    private function path(string $key): string
    {
        return $this->dir . '/' . sha1($key) . '.cache';
    }
}
