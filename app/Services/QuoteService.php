<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuoteService
{
    /** @param array<int, array{product_id:int, quantity:int}> $items */
    public function create(array $items, Customer $customer, ?string $note): Quote
    {
        return DB::transaction(function () use ($items, $customer, $note) {
            $quote = Quote::create([
                'public_token' => Str::random(40),
                'customer_id' => $customer->id,
                'note' => $note,
                'usd_rate' => Setting::usdRate(),
            ]);
            $quote->update(['number' => sprintf('COT-%04d', $quote->id)]);

            collect($items)->groupBy('product_id')->each(function ($lines, $productId) use ($quote) {
                $product = Product::findOrFail($productId);
                $quote->items()->create([
                    'product_id' => $product->id,
                    'code' => $product->code,
                    'measures' => $product->measures,
                    'quantity' => $lines->sum('quantity'),
                    'unit_price_cents' => $product->price_cents,
                ]);
            });

            return $quote->refresh()->load('items.product');
        });
    }

    public function convert(Quote $quote, User $user): Order
    {
        return DB::transaction(function () use ($quote) {
            $locked = Quote::lockForUpdate()->with('items.product')->findOrFail($quote->id);

            if ($locked->order_id) {
                return Order::findOrFail($locked->order_id);
            }

            $order = Order::create([
                'customer_id' => $locked->customer_id,
                'status' => OrderStatus::New,
                'source' => 'quote',
                'note' => $locked->note,
                'usd_rate' => $locked->usd_rate,
            ]);
            $order->update(['number' => sprintf('GDM-%04d', $order->id)]);

            $locked->items->each(fn (QuoteItem $item) => $order->items()->create([
                'product_id' => $item->product_id,
                'code' => $item->code,
                'measures' => $item->measures,
                'quantity' => $item->quantity,
                'unit_price_cents' => $item->unit_price_cents,
                'unit_cost_cents' => $item->product->avg_cost_cents,
            ]));

            $locked->update(['order_id' => $order->id]);
            $quote->refresh();

            return $order->load('items');
        });
    }
}
