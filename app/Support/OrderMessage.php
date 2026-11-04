<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;

class OrderMessage
{
    public static function for(Order $order, string $name): string
    {
        $business = Setting::get('business_name', 'Rodamiento GDM');
        $lines = $order->items->map(function (OrderItem $item) {
            $label = "- {$item->quantity} x {$item->code}".($item->measures ? " ({$item->measures})" : '');
            $price = $item->unit_price_cents === null
                ? 'Consultar precio'
                : Money::usd($item->quantity * $item->unit_price_cents);

            return "{$label}: {$price}";
        })->implode("\n");

        $total = $order->totalCents();
        $totalText = $total === null ? 'por confirmar' : Money::usd($total);

        return "Hola, {$business}. Soy {$name}. Mi pedido es {$order->number}:\n\n{$lines}\n\n"
            ."Total estimado: {$totalText}\n¿Tienen disponibilidad?";
    }
}
