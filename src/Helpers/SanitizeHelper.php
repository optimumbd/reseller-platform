<?php

declare(strict_types=1);

namespace App\Helpers;

final class SanitizeHelper
{
    public static function html(string $value, array $allowedTags = ['<p>', '<br>', '<strong>', '<em>', '<a>', '<ul>', '<ol>', '<li>', '<h1>', '<h2>', '<h3>', '<h4>', '<h5>', '<h6>', '<blockquote>', '<code>', '<pre>', '<img>']): string
    {
        return strip_tags($value, $allowedTags);
    }

    public static function filename(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?? $name;
        return trim($name, '-');
    }

    public static function slug(string $value): string
    {
        $value = trim(strtolower($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? $value;
        return trim($value, '-');
    }
}
