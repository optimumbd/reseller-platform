<?php

declare(strict_types=1);

/*
 * Test bootstrap.
 *
 * The runtime helper file (`src/Helpers/functions.php`) defines `config()`,
 * `env()`, `url()` etc. but they internally reach into the booted `App`
 * singleton — which we deliberately do NOT boot for unit tests.
 *
 * We pre-define minimal stubs for those helpers (under `function_exists`
 * guards) so loading the helper file is a no-op for them, and our drivers
 * can still call `config(...)` / `url(...)` without crashing.
 */

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return $GLOBALS['__test_config'][$key] ?? $default;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return ($value === false || $value === null) ? $default : $value;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = (string) ($GLOBALS['__test_config']['app.url'] ?? 'http://localhost');
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

require __DIR__ . '/../vendor/autoload.php';

// PSR-4 autoloader for `Tests\` namespace under tests/.
spl_autoload_register(static function (string $class): void {
    $prefix = 'Tests\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
