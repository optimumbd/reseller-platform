<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Plain-PHP template renderer with theme fallback.
 *
 * Lookup order for `render('foo/bar')`:
 *   1. themes/{active_theme}/templates/foo/bar.php
 *   2. templates/foo/bar.php
 */
final class View
{
    /** @var array<string,mixed> */
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function render(string $template, array $data = []): string
    {
        $app = App::getInstance();
        $activeTheme = $app->config('app.theme', 'default');

        $candidates = [
            $app->basePath("themes/{$activeTheme}/templates/{$template}.php"),
            $app->templatesPath("{$template}.php"),
        ];

        $file = null;
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                $file = $candidate;
                break;
            }
        }
        if ($file === null) {
            throw new \RuntimeException("View not found: {$template}");
        }

        $merged = array_merge(self::$shared, $data);
        return self::renderFile($file, $merged);
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function partial(string $template, array $data = []): string
    {
        return self::render('partials/' . $template, $data);
    }

    /**
     * @param array<string,mixed> $data
     */
    private static function renderFile(string $__file, array $__data): string
    {
        extract($__data, EXTR_SKIP);
        ob_start();
        try {
            include $__file;
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}
