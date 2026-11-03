<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['usd_rate' => 'decimal:4'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function totalCents(): ?int
    {
        if ($this->items->contains(fn (QuoteItem $i) => $i->unit_price_cents === null)) {
            return null;
        }

        return $this->items->sum(fn (QuoteItem $i) => $i->quantity * $i->unit_price_cents);
    }
}
