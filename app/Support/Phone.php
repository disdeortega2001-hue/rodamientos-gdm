<?php

namespace App\Support;

class Phone
{
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        $digits = preg_replace('/^00/', '', $digits) ?? '';

        if (str_starts_with($digits, '58') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        }

        $local = ltrim($digits, '0');

        return strlen($local) === 10 ? '0'.$local : $digits;
    }

    public static function international(string $phone): string
    {
        $normalized = self::normalize($phone);

        return str_starts_with($normalized, '0') ? '58'.ltrim($normalized, '0') : $normalized;
    }
}
