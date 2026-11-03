<?php

namespace App\Support;

use App\Models\Setting;

class WhatsApp
{
    public static function url(string $text): string
    {
        return self::urlTo(Setting::get('whatsapp_number', '584129477195'), $text);
    }

    public static function urlTo(string $number, string $text): string
    {
        return 'https://wa.me/'.$number.'?text='.rawurlencode($text);
    }
}
