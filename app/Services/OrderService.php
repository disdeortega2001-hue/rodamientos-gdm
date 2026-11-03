<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidTransition;
use App\Exceptions\MissingPrice;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private StockService $stock) {}

    /** @param array<int, array{product_id:int, quantity:int}> $items */
    public function create(array $items, Customer $customer, string $source, ?string $note, ?string $token = null): Order
    {
        if ($token !== null && ($existing = Order::where('checkout_token', $token)->first())) {
            return $existing->load('items');
        }

        try {
            return DB::transaction(function () use ($items, $customer, $source, $note, $token) {
                $order = Order::create([
                    'customer_id' => $customer->id,
                    'status' => OrderStatus::New,
                    'source' => $source,
                    'note' => $note,
                    'usd_rate' => Setting::usdRate(),
                    'checkout_token' => $token,
                ]);
                $order->update(['number' => sprintf('GDM-%04d', $order->id)]);

                collect($items)->groupBy('product_id')->each(function ($lines, $productId) use ($order) {
                    $product = Product::findOrFail($productId);
                    $order->items()->create([
                        'product_id' => $product->id,
                        'code' => $product->code,
                        'measures' => $product->measures,
                        'quantity' => $lines->sum('quantity'),
                        'unit_price_cents' => $product->price_cents,
                        'unit_cost_cents' => $product->avg_cost_cents,
                    ]);
                });

                return $order->refresh()->load('items');
            });
        } catch (UniqueConstraintViolationException $e) {
            if ($token === null) {
                throw $e;
            }

            return Order::where('checkout_token', $token)->firstOrFail()->load('items');
        }
    }

    public function confirm(Order $order, User $user): void
    {
        $this->transition($order, function (Order $locked) use ($user) {
            if ($locked->status !== OrderStatus::New) {
                throw InvalidTransition::for($locked->status->label(), 'confirmar');
            }

            if ($missing = $locked->items()->whereNull('unit_price_cents')->first()) {
                throw MissingPrice::for($missing->code);
            }

            $this->stock->consumeForOrder($locked, $user);
            $locked->update(['status' => OrderStatus::Confirmed, 'confirmed_at' => now()]);
        });
    }

    public function deliver(Order $order, User $user): void
    {
        $this->transition($order, function (Order $locked) {
            if ($locked->status !== OrderStatus::Confirmed) {
                throw InvalidTransition::for($locked->status->label(), 'entregar');
            }

            $locked->update(['status' => OrderStatus::Delivered, 'delivered_at' => now()]);
        });
    }

    public function cancel(Order $order, User $user): void
    {
        $this->transition($order, function (Order $locked) use ($user) {
            if (! in_array($locked->status, [OrderStatus::New, OrderStatus::Confirmed], true)) {
                throw InvalidTransition::for($locked->status->label(), 'cancelar');
            }

            if ($locked->status === OrderStatus::Confirmed) {
                $this->stock->restoreForOrder($locked, $user);
            }

            $locked->update(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);
        });
    }

    private function transition(Order $order, callable $change): void
    {
        DB::transaction(function () use ($order, $change) {
            $change(Order::lockForUpdate()->findOrFail($order->id));
        });

        $order->refresh();
    }
}
