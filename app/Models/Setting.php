<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    private const DEFAULTS = [
        'address' => 'Valle de la Pascua, estado Guárico',
        'hours' => 'Siempre disponible',
        'instagram' => 'rodamientogdm',
        'payment_methods' => 'Pago móvil',
    ];

    private const FORMER_BUSINESS_NAME = 'Rodamientos'.' GDM';

    protected $guarded = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::where('key', $key)->value('value');

        if ($key === 'business_name' && $value === self::FORMER_BUSINESS_NAME) {
            return 'Rodamiento GDM';
        }

        return $value !== null && $value !== '' ? $value : ($default ?? self::DEFAULTS[$key] ?? null);
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function usdRate(): ?string
    {
        $rate = static::get('usd_rate');

        return is_numeric($rate) && (float) $rate > 0 ? $rate : null;
    }
}
