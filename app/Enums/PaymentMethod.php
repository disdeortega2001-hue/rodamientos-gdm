<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PagoMovil = 'pago_movil';
    case Transfer = 'transfer';
    case Cash = 'cash';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PagoMovil => 'Pago móvil',
            self::Transfer => 'Transferencia',
            self::Cash => 'Efectivo',
            self::Other => 'Otro',
        };
    }
}
