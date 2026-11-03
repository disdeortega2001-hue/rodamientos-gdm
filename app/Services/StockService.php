<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Exceptions\InsufficientStock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    public function entry(Product $product, int $qty, int $unitCostCents, User $user, ?string $note): StockMovement
    {
        if ($qty <= 0 || $unitCostCents < 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor que cero y el costo no puede ser negativo.');
        }

        return DB::transaction(function () use ($product, $qty, $unitCostCents, $user, $note) {
            $locked = Product::lockForUpdate()->findOrFail($product->id);
            $current = max(0, $locked->stock);

            $locked->avg_cost_cents = (int) round(
                ($current * $locked->avg_cost_cents + $qty * $unitCostCents) / ($current + $qty)
            );
            $locked->stock += $qty;
            $locked->save();

            return $this->record($locked, MovementType::Entry, $qty, $unitCostCents, $user, null, $note);
        });
    }

    public function adjust(Product $product, int $delta, string $reason, User $user): StockMovement
    {
        if ($delta === 0 || trim($reason) === '') {
            throw new InvalidArgumentException('El ajuste necesita una cantidad distinta de cero y un motivo.');
        }

        return DB::transaction(function () use ($product, $delta, $reason, $user) {
            $locked = Product::lockForUpdate()->findOrFail($product->id);

            if ($locked->stock + $delta < 0) {
                throw InsufficientStock::for($locked->code, $locked->stock);
            }

            $locked->stock += $delta;
            $locked->save();

            return $this->record($locked, MovementType::Adjustment, $delta, null, $user, null, trim($reason));
        });
    }

    /** Must run inside the caller's transaction, so a failure on any line reverts all of them. */
    public function consumeForOrder(Order $order, User $user): void
    {
        $items = $order->items()->orderBy('product_id')->get();

        $items->each(function (OrderItem $item) use ($order, $user) {
            $product = Product::lockForUpdate()->findOrFail($item->product_id);
            $item->update(['unit_cost_cents' => $product->avg_cost_cents]);

            if (! $product->track_stock) {
                return;
            }

            if ($product->stock < $item->quantity) {
                throw InsufficientStock::for($product->code, $product->stock);
            }

            $product->stock -= $item->quantity;
            $product->save();

            $this->record($product, MovementType::Sale, -$item->quantity, $product->avg_cost_cents, $user, $order, $order->number);
        });
    }

    /** Must run inside the caller's transaction. Returns exactly what the order's sale movements took. */
    public function restoreForOrder(Order $order, User $user): void
    {
        StockMovement::where('order_id', $order->id)->where('type', MovementType::Sale)->orderBy('product_id')->get()
            ->each(function (StockMovement $sale) use ($order, $user) {
                $product = Product::lockForUpdate()->findOrFail($sale->product_id);
                $product->stock += abs($sale->quantity);
                $product->save();

                $this->record($product, MovementType::SaleReversal, abs($sale->quantity), $sale->unit_cost_cents, $user, $order, $order->number);
            });
    }

    private function record(Product $product, MovementType $type, int $quantity, ?int $cost, ?User $user, ?Order $order, ?string $note): StockMovement
    {
        return StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'order_id' => $order?->id,
            'type' => $type,
            'quantity' => $quantity,
            'unit_cost_cents' => $cost,
            'note' => $note,
        ]);
    }
}
