<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SiteSetting;

final class SettingService
{
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return SiteSetting::get($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        SiteSetting::set($key, $value, $group);
    }
}
