<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'usd_rate' => 'decimal:4',
            'confirmed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'delivery_note_issued_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    public function totalCents(): ?int
    {
        if ($this->items->contains(fn (OrderItem $i) => $i->unit_price_cents === null)) {
            return null;
        }

        return $this->items->sum(fn (OrderItem $i) => $i->quantity * $i->unit_price_cents);
    }

    public function paidCents(): int
    {
        return (int) $this->payments->sum('amount_cents');
    }

    public function paymentStatus(): PaymentStatus
    {
        $paid = $this->paidCents();
        $total = $this->totalCents();

        return match (true) {
            $paid <= 0 => PaymentStatus::Pending,
            $total !== null && $paid >= $total => PaymentStatus::Paid,
            default => PaymentStatus::Partial,
        };
    }
}
