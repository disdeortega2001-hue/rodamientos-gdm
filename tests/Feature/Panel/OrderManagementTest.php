<?php

namespace Tests\Feature\Panel;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    private function webOrder(array $productAttrs = [], int $qty = 2): Order
    {
        $product = $this->product($productAttrs);

        return app(OrderService::class)->create([['product_id' => $product->id, 'quantity' => $qty]], $this->customer(), 'web', null);
    }

    public function test_counter_order_created_with_source_counter(): void
    {
        $p = $this->product(['price_cents' => 600]);
        $this->actingAs($this->seller())->post('/panel/pedidos', [
            'name' => 'Luis Rojas',
            'phone' => '0414-555-1234',
            'items' => [['product_id' => $p->id, 'quantity' => 3]],
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('counter', $order->source);
        $this->assertSame('04145551234', $order->customer->phone);
        $this->assertSame(1800, $order->totalCents());
    }

    public function test_seller_confirms_order(): void
    {
        $order = $this->webOrder(['stock' => 5]);
        $this->actingAs($this->seller())->post("/panel/pedidos/{$order->id}/confirmar")->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(OrderStatus::Confirmed, $order->refresh()->status);
        $this->assertSame(3, $order->items->first()->product->stock);
    }

    public function test_insufficient_stock_shows_message(): void
    {
        $order = $this->webOrder(['stock' => 1]);
        $this->actingAs($this->seller())->post("/panel/pedidos/{$order->id}/confirmar")->assertSessionHasErrors('order');
        $this->assertSame(OrderStatus::New, $order->refresh()->status);
    }

    public function test_deliver_and_cancel_actions(): void
    {
        $order = $this->webOrder();
        $this->actingAs($this->seller());
        $this->post("/panel/pedidos/{$order->id}/confirmar");
        $this->post("/panel/pedidos/{$order->id}/entregar")->assertSessionHasNoErrors();
        $this->assertSame(OrderStatus::Delivered, $order->refresh()->status);
        $this->post("/panel/pedidos/{$order->id}/cancelar")->assertSessionHasErrors('order');
    }

    public function test_staff_sets_price_on_new_order_line(): void
    {
        $order = $this->webOrder(['price_cents' => null]);
        $item = $order->items->first();
        $this->actingAs($this->seller());
        $this->post("/panel/pedidos/{$order->id}/confirmar")->assertSessionHasErrors('order');
        $this->put("/panel/pedidos/{$order->id}/lineas/{$item->id}", ['price' => '30'])->assertSessionHasNoErrors();
        $this->assertSame(3000, $item->refresh()->unit_price_cents);
        $this->post("/panel/pedidos/{$order->id}/confirmar")->assertSessionHasNoErrors();
    }

    public function test_line_price_cannot_change_after_confirmation(): void
    {
        $order = $this->webOrder();
        $this->actingAs($this->seller());
        $this->post("/panel/pedidos/{$order->id}/confirmar");
        $item = $order->items->first();
        $this->put("/panel/pedidos/{$order->id}/lineas/{$item->id}", ['price' => '1'])->assertSessionHasErrors();
        $this->assertSame(600, $item->refresh()->unit_price_cents);
    }

    public function test_line_of_other_order_is_404(): void
    {
        $order = $this->webOrder();
        $other = $this->webOrder(['code' => 'OTRO']);
        $this->actingAs($this->seller())->put("/panel/pedidos/{$order->id}/lineas/{$other->items->first()->id}", ['price' => '1'])->assertNotFound();
    }

    public function test_filter_by_status(): void
    {
        $new = $this->webOrder();
        $done = $this->webOrder(['code' => 'OTRO']);
        $seller = $this->seller();
        app(OrderService::class)->confirm($done, $seller);
        $this->actingAs($seller)->get('/panel/pedidos?estado=new')->assertSee($new->number)->assertDontSee($done->number);
    }

    public function test_customer_history_lists_orders(): void
    {
        $order = $this->webOrder();
        $this->actingAs($this->seller())->get("/panel/clientes/{$order->customer_id}")->assertOk()->assertSee($order->number)->assertSee('Ana Pérez');
        $this->get('/panel/clientes?q=ana')->assertSee('Ana Pérez');
        $this->get('/panel/clientes?q=zzz')->assertDontSee('Ana Pérez');
    }

    public function test_name_is_escaped(): void
    {
        $p = $this->product();
        $order = app(OrderService::class)->create([['product_id' => $p->id, 'quantity' => 1]],
            \App\Models\Customer::create(['name' => '<script>alert(1)</script>', 'phone' => '04120000000']), 'web', '<script>nota</script>');
        $this->actingAs($this->seller())->get("/panel/pedidos/{$order->id}")->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<script>nota</script>', false);
    }

    public function test_seller_order_page_hides_cost_and_profit(): void
    {
        $order = $this->webOrder(['avg_cost_cents' => 987654]);
        $this->actingAs($this->seller())->get("/panel/pedidos/{$order->id}")->assertOk()->assertDontSee('9.876,54')->assertDontSee('Ganancia');
        $this->actingAs($this->admin())->get("/panel/pedidos/{$order->id}")->assertSee('Ganancia');
    }

    public function test_panel_routes_require_login(): void
    {
        $this->get('/panel/pedidos')->assertRedirect('/panel/acceso');
        $this->get('/panel/clientes')->assertRedirect('/panel/acceso');
    }
}
