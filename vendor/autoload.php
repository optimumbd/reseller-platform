<?php

/*
 * Minimal Composer-compatible autoloader that ships with the project so that
 * shared-hosting installs do NOT require `composer install`.
 *
 * If you run `composer install`, this file is replaced by Composer's generated
 * autoloader and everything keeps working.
 *
 * It implements PSR-4 for the `App\` namespace + a fixed list of helper files,
 * mirroring the autoload section of composer.json.
 */

declare(strict_types=1);

if (defined('RP_AUTOLOADER_LOADED')) {
    return;
}
define('RP_AUTOLOADER_LOADED', true);

$baseDir = dirname(__DIR__);

spl_autoload_register(static function (string $class) use ($baseDir): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    $file = $baseDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $relativePath;
    if (is_file($file)) {
        require $file;
    }
});

// Files autoloaded eagerly (helpers).
$helperFiles = [
    $baseDir . '/src/Helpers/functions.php',
];
foreach ($helperFiles as $helperFile) {
    if (is_file($helperFile)) {
        require_once $helperFile;
    }
}
