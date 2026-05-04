<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Local filesystem storage. S3/Wasabi/R2 drivers can wrap this interface.
 */
final class Storage
{
    public function __construct(private readonly string $root)
    {
        if (!is_dir($this->root)) {
            @mkdir($this->root, 0775, true);
        }
    }

    public function put(string $relativePath, string $contents): bool
    {
        $full = $this->fullPath($relativePath);
        $dir = dirname($full);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return (bool) @file_put_contents($full, $contents, LOCK_EX);
    }

    public function get(string $relativePath): ?string
    {
        $full = $this->fullPath($relativePath);
        if (!is_file($full)) {
            return null;
        }
        $data = @file_get_contents($full);
        return $data === false ? null : $data;
    }

    public function exists(string $relativePath): bool
    {
        return is_file($this->fullPath($relativePath));
    }

    public function delete(string $relativePath): bool
    {
        $full = $this->fullPath($relativePath);
        if (!is_file($full)) {
            return true;
        }
        return @unlink($full);
    }

    public function fullPath(string $relativePath): string
    {
        return $this->root . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
    }
}
