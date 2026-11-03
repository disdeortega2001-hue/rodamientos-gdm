<?php

namespace App\Enums;

enum MovementType: string
{
    case Opening = 'opening';
    case Entry = 'entry';
    case Sale = 'sale';
    case SaleReversal = 'sale_reversal';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Saldo inicial',
            self::Entry => 'Entrada',
            self::Sale => 'Venta',
            self::SaleReversal => 'Devolución por cancelación',
            self::Adjustment => 'Ajuste',
        };
    }
}
