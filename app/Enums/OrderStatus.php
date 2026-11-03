<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nuevo',
            self::Confirmed => 'Confirmado',
            self::Delivered => 'Entregado',
            self::Cancelled => 'Cancelado',
        };
    }
}
