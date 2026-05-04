<?php

declare(strict_types=1);

namespace App\Helpers;

final class IdnHelper
{
    public static function toAscii(string $domain): string
    {
        if (function_exists('idn_to_ascii')) {
            $ascii = @idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            return $ascii !== false ? $ascii : $domain;
        }
        return $domain;
    }

    public static function toUnicode(string $domain): string
    {
        if (function_exists('idn_to_utf8')) {
            $unicode = @idn_to_utf8($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            return $unicode !== false ? $unicode : $domain;
        }
        return $domain;
    }
}
