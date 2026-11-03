<?php

namespace App\Support;

class Money
{
    public static function usd(int $cents): string
    {
        $decimals = $cents % 100 === 0 ? 0 : 2;

        return ($cents < 0 ? '-' : '').'$'.number_format(abs($cents) / 100, $decimals, ',', '.');
    }

    public static function bs(int $cents, string $rate): string
    {
        return 'Bs. '.number_format(self::toBsCents($cents, $rate) / 100, 2, ',', '.');
    }

    public static function toBsCents(int $cents, string $rate): int
    {
        return (int) round($cents * (float) $rate);
    }

    public static function parseCents(string|int|float|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) round((float) str_replace(',', '.', (string) $value) * 100);
    }

    public static function input(?int $cents): string
    {
        return $cents === null ? '' : number_format($cents / 100, 2, '.', '');
    }
}
