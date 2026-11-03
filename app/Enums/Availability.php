<?php

namespace App\Enums;

enum Availability: string
{
    case Available = 'available';
    case Low = 'low';
    case Out = 'out';
    case Ask = 'ask';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Low => 'Pocas unidades',
            self::Out => 'Agotado',
            self::Ask => 'Consultar',
        };
    }
}
