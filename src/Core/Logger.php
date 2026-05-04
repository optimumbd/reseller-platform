<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tiny PSR-3-ish file logger.
 */
final class Logger
{
    public function __construct(private readonly string $file) {}

    public function debug(string $message, array $context = []): void { $this->log('DEBUG', $message, $context); }
    public function info(string $message, array $context = []): void  { $this->log('INFO', $message, $context); }
    public function notice(string $message, array $context = []): void{ $this->log('NOTICE', $message, $context); }
    public function warning(string $message, array $context = []): void { $this->log('WARNING', $message, $context); }
    public function error(string $message, array $context = []): void { $this->log('ERROR', $message, $context); }
    public function critical(string $message, array $context = []): void { $this->log('CRITICAL', $message, $context); }

    private function log(string $level, string $message, array $context): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : ''
        );
        @file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);
    }
}
