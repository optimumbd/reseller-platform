<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Plain-PHP template renderer with theme fallback.
 *
 * Lookup order for `render('foo/bar')`:
 *   1. themes/{active_theme}/templates/foo/bar.php
 *   2. templates/foo/bar.php
 *
 * Layout system:
 *   At the top of a template call `\App\Core\View::extend('layouts/app', ['title' => '...'])`.
 *   The contents of the template are captured via output buffering and made
 *   available to the layout as `$content`.
 */
final class View
{
    /** @var array<string,mixed> */
    private static array $shared = [];

    /** @var array<int,array{name:string,data:array}> */
    private static array $layoutStack = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function extend(string $layout, array $data = []): void
    {
        self::$layoutStack[] = ['name' => $layout, 'data' => $data];
        ob_start();
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
        $stackBefore = count(self::$layoutStack);
        $obLevelBefore = ob_get_level();
        ob_start();
        try {
            include $__file;
        } catch (\Throwable $e) {
            // Clear our and any nested buffers opened by extend().
            while (ob_get_level() > $obLevelBefore) {
                @ob_end_clean();
            }
            // Pop any layouts pushed during the failed render.
            while (count(self::$layoutStack) > $stackBefore) {
                array_pop(self::$layoutStack);
            }
            throw $e;
        }

        // If extend() was called, the captured content is in the inner buffer.
        if (count(self::$layoutStack) > $stackBefore) {
            $output = '';
            while (count(self::$layoutStack) > $stackBefore) {
                $entry = array_pop(self::$layoutStack);
                $captured = (string) ob_get_clean();
                $layoutData = array_merge($entry['data'], ['content' => $captured]);
                $output = self::renderRaw($entry['name'], $layoutData);
            }
            // Discard the now-empty outermost buffer started above.
            @ob_end_clean();
            return $output;
        }

        return (string) ob_get_clean();
    }

    private static function renderRaw(string $template, array $data): string
    {
        $app = App::getInstance();
        $activeTheme = $app->config('app.theme', 'default');
        $candidates = [
            $app->basePath("themes/{$activeTheme}/templates/{$template}.php"),
            $app->templatesPath("{$template}.php"),
        ];
        foreach ($candidates as $c) {
            if (file_exists($c)) {
                return self::renderFile($c, array_merge(self::$shared, $data));
            }
        }
        throw new \RuntimeException("Layout not found: {$template}");
    }
}
