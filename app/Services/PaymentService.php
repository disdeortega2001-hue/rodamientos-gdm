<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\OverPayment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    public function record(Order $order, PaymentMethod $method, int $amountCents, string $paidAt, ?string $reference, User $user): Payment
    {
        return DB::transaction(function () use ($order, $method, $amountCents, $paidAt, $reference, $user) {
            $locked = Order::lockForUpdate()->with(['items', 'payments'])->findOrFail($order->id);

            if ($locked->status === OrderStatus::Cancelled) {
                throw new RuntimeException('No se pueden registrar pagos en un pedido cancelado.');
            }

            $total = $locked->totalCents();
            if ($total === null) {
                throw new RuntimeException('Asigna el precio de todas las piezas antes de registrar un pago.');
            }

            if ($locked->paidCents() + $amountCents > $total) {
                throw new OverPayment;
            }

            return $locked->payments()->create([
                'user_id' => $user->id,
                'method' => $method,
                'reference' => $reference,
                'amount_cents' => $amountCents,
                'paid_at' => $paidAt,
            ]);
        });
    }
}
