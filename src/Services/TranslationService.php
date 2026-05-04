<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

final class TranslationService
{
    /** @var array<string,array<string,string>> */
    private static array $cache = [];

    public static function translate(string $key, array $replace = []): string
    {
        $locale = (string) (App::getInstance()->session->get('locale') ?? config('app.locale', 'en'));
        $strings = self::load($locale);
        $value = $strings[$key] ?? null;
        if ($value === null) {
            $fallback = (string) config('app.fallback_locale', 'en');
            if ($fallback !== $locale) {
                $strings = self::load($fallback);
                $value = $strings[$key] ?? null;
            }
        }
        if ($value === null) {
            return $key;
        }
        foreach ($replace as $k => $v) {
            $value = str_replace(':' . $k, (string) $v, $value);
        }
        return $value;
    }

    /** @return array<string,string> */
    private static function load(string $locale): array
    {
        if (isset(self::$cache[$locale])) {
            return self::$cache[$locale];
        }
        $dir = App::getInstance()->basePath('lang/' . $locale);
        $merged = [];
        if (is_dir($dir)) {
            foreach (glob($dir . '/*.php') as $file) {
                $arr = require $file;
                if (is_array($arr)) {
                    $namespace = basename($file, '.php');
                    foreach ($arr as $k => $v) {
                        $merged[$namespace . '.' . $k] = (string) $v;
                    }
                }
            }
        }
        return self::$cache[$locale] = $merged;
    }
}
