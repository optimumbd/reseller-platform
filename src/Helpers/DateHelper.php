<?php

declare(strict_types=1);

namespace App\Helpers;

final class DateHelper
{
    public static function format(string|int|null $date, string $format = 'Y-m-d H:i'): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        $ts = is_numeric($date) ? (int) $date : strtotime((string) $date);
        return $ts ? date($format, $ts) : '';
    }

    public static function diffForHumans(string|int $date): string
    {
        $ts = is_numeric($date) ? (int) $date : strtotime((string) $date);
        if (!$ts) {
            return '';
        }
        $diff = time() - $ts;
        $past = $diff >= 0;
        $diff = abs($diff);
        $units = [
            31536000 => 'year', 2592000 => 'month', 86400 => 'day',
            3600 => 'hour', 60 => 'minute', 1 => 'second',
        ];
        foreach ($units as $secs => $name) {
            if ($diff >= $secs) {
                $val = (int) floor($diff / $secs);
                $plural = $val === 1 ? '' : 's';
                return $past ? "{$val} {$name}{$plural} ago" : "in {$val} {$name}{$plural}";
            }
        }
        return 'just now';
    }
}
