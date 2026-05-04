<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\App;

final class SiteSetting
{
    /** @var array<string,mixed>|null */
    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$cache === null) {
            try {
                $rows = App::getInstance()->db->select('SELECT `key`, `value` FROM site_settings');
                $map = [];
                foreach ($rows as $row) {
                    $map[(string) $row['key']] = $row['value'];
                }
                self::$cache = $map;
            } catch (\Throwable) {
                self::$cache = [];
            }
        }
        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        $db = App::getInstance()->db;
        $exists = (int) $db->scalar('SELECT COUNT(*) FROM site_settings WHERE `key` = :k', ['k' => $key]) > 0;
        if ($exists) {
            $db->execute('UPDATE site_settings SET `value` = :v, `group` = :g WHERE `key` = :k', [
                'v' => is_scalar($value) ? (string) $value : json_encode($value),
                'g' => $group,
                'k' => $key,
            ]);
        } else {
            $db->insert('site_settings', [
                'key' => $key,
                'value' => is_scalar($value) ? (string) $value : json_encode($value),
                'group' => $group,
            ]);
        }
        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
    }
}
