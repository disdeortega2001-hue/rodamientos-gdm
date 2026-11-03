<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Exceptions\InsufficientStock;
use App\Exceptions\InvalidTransition;
use App\Exceptions\MissingPrice;
use App\Models\Order;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    private function service(): OrderService
    {
        return app(OrderService::class);
    }

    private function order(array $products, int $quantity = 2): Order
    {
        $items = array_map(fn ($p) => ['product_id' => $p->id, 'quantity' => $quantity], $products);

        return $this->service()->create($items, $this->customerOnce(), 'web', null);
    }

    private function customerOnce()
    {
        return \App\Models\Customer::firstOrCreate(['phone' => '04121234567'], ['name' => 'Ana Pérez']);
    }

    public function test_create_order_uses_server_price_rate_and_number(): void
    {
        Setting::put('usd_rate', '186.42');
        $p = $this->product(['price_cents' => 600, 'avg_cost_cents' => 360, 'stock' => 5, 'code' => 'A1', 'measures' => '1 x 2']);
        $order = $this->order([$p]);

        $this->assertSame('GDM-0001', $order->number);
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertSame('186.4200', (string) $order->usd_rate);
        $item = $order->items->first();
        $this->assertSame(600, $item->unit_price_cents);
        $this->assertSame('A1', $item->code);
        $this->assertSame('1 x 2', $item->measures);
        $this->assertSame(5, $p->refresh()->stock);
    }

    public function test_create_merges_repeated_products(): void
    {
        $p = $this->product();
        $order = $this->service()->create([
            ['product_id' => $p->id, 'quantity' => 1],
            ['product_id' => $p->id, 'quantity' => 2],
        ], $this->customerOnce(), 'web', null);
        $this->assertCount(1, $order->items);
        $this->assertSame(3, $order->items->first()->quantity);
    }

    public function test_same_token_returns_same_order(): void
    {
        $p = $this->product();
        $items = [['product_id' => $p->id, 'quantity' => 1]];
        $a = $this->service()->create($items, $this->customerOnce(), 'web', null, 'tok-1');
        $b = $this->service()->create($items, $this->customerOnce(), 'web', null, 'tok-1');
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, Order::count());
    }

    public function test_confirm_consumes_stock_and_writes_movement(): void
    {
        $user = $this->seller();
        $p = $this->product(['stock' => 5]);
        $order = $this->order([$p]);
        $this->service()->confirm($order, $user);

        $this->assertSame(3, $p->refresh()->stock);
        $this->assertSame(OrderStatus::Confirmed, $order->refresh()->status);
        $this->assertNotNull($order->confirmed_at);
        $m = StockMovement::firstOrFail();
        $this->assertSame(MovementType::Sale, $m->type);
        $this->assertSame(-2, $m->quantity);
        $this->assertSame($user->id, $m->user_id);
        $this->assertSame($order->id, $m->order_id);
    }

    public function test_confirm_rejects_when_insufficient(): void
    {
        $p = $this->product(['stock' => 1]);
        $other = $this->product(['stock' => 5]);
        $order = $this->order([$other, $p]);

        try {
            $this->service()->confirm($order, $this->seller());
            $this->fail('Se esperaba InsufficientStock');
        } catch (InsufficientStock) {
            $this->assertSame(OrderStatus::New, $order->refresh()->status);
            $this->assertSame(1, $p->refresh()->stock);
            $this->assertSame(5, $other->refresh()->stock, 'La transacción debe revertir todo');
        }
    }

    public function test_second_confirm_of_last_unit_fails(): void
    {
        $p = $this->product(['stock' => 1]);
        $first = $this->order([$p], 1);
        $second = $this->order([$p], 1);
        $this->service()->confirm($first, $this->seller());

        $this->expectException(InsufficientStock::class);
        try {
            $this->service()->confirm($second, $this->seller());
        } finally {
            $this->assertSame(0, $p->refresh()->stock);
        }
    }

    public function test_confirm_rejects_missing_price(): void
    {
        $this->expectException(MissingPrice::class);
        $this->service()->confirm($this->order([$this->product(['price_cents' => null])]), $this->seller());
    }

    public function test_confirm_skips_stock_for_untracked_product(): void
    {
        $p = $this->product(['track_stock' => false, 'stock' => 0]);
        $this->service()->confirm($this->order([$p]), $this->seller());
        $this->assertSame(0, StockMovement::count());
        $this->assertSame(0, $p->refresh()->stock);
    }

    public function test_cancel_confirmed_restores_stock_once(): void
    {
        $p = $this->product(['stock' => 5]);
        $order = $this->order([$p]);
        $user = $this->seller();
        $this->service()->confirm($order, $user);
        $this->service()->cancel($order, $user);
        $this->assertSame(5, $p->refresh()->stock);
        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);

        try {
            $this->service()->cancel($order, $user);
            $this->fail('Se esperaba InvalidTransition');
        } catch (InvalidTransition) {
            $this->assertSame(5, $p->refresh()->stock);
        }
    }

    public function test_cancel_new_does_not_touch_stock(): void
    {
        $p = $this->product(['stock' => 5]);
        $this->service()->cancel($this->order([$p]), $this->seller());
        $this->assertSame(0, StockMovement::count());
    }

    public function test_delivered_cannot_be_cancelled(): void
    {
        $order = $this->order([$this->product()]);
        $user = $this->seller();
        $this->service()->confirm($order, $user);
        $this->service()->deliver($order, $user);
        $this->assertNotNull($order->refresh()->delivered_at);
        $this->expectException(InvalidTransition::class);
        $this->service()->cancel($order, $user);
    }

    public function test_cannot_deliver_new_order(): void
    {
        $this->expectException(InvalidTransition::class);
        $this->service()->deliver($this->order([$this->product()]), $this->seller());
    }

    public function test_confirm_refreshes_unit_cost(): void
    {
        $p = $this->product(['avg_cost_cents' => 360]);
        $order = $this->order([$p]);
        $p->update(['avg_cost_cents' => 400]);
        $this->service()->confirm($order, $this->seller());
        $this->assertSame(400, $order->items()->first()->unit_cost_cents);
    }

    public function test_old_items_keep_price_after_product_change(): void
    {
        $p = $this->product(['price_cents' => 600]);
        $order = $this->order([$p]);
        $p->update(['price_cents' => 900]);
        $this->assertSame(600, $order->items()->first()->unit_price_cents);
    }
}
