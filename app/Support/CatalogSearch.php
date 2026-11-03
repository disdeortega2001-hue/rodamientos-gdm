<?php

namespace App\Support;

use Illuminate\Support\Str;

class CatalogSearch
{
    public static function normalize(string $text): string
    {
        $clean = preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($text))) ?? '';

        return preg_replace('/(?<=\d)x(?=\d)/', '', $clean) ?? '';
    }
}
