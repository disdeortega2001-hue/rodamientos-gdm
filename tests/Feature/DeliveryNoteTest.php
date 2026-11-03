<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class DeliveryNoteTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    private function order(array $productAttrs = [], int $qty = 2): Order
    {
        $product = $this->product($productAttrs + ['code' => 'P'.uniqid(), 'price_cents' => 600, 'avg_cost_cents' => 987654, 'stock' => 432198]);

        return app(OrderService::class)->create([['product_id' => $product->id, 'quantity' => $qty]], $this->customer(), 'web', null);
    }

    private function confirmed(array $productAttrs = []): Order
    {
        $order = $this->order($productAttrs);
        app(OrderService::class)->confirm($order, $this->seller());

        return $order->refresh();
    }

    public function test_new_order_has_no_note(): void
    {
        $this->assertNull($this->order()->delivery_note_number);
    }

    public function test_confirming_issues_a_sequential_note(): void
    {
        $first = $this->confirmed();
        $second = $this->confirmed();
        $this->assertSame('NE-0001', $first->delivery_note_number);
        $this->assertSame('NE-0002', $second->delivery_note_number);
        $this->assertSame(40, strlen($first->delivery_note_token));
        $this->assertNotNull($first->delivery_note_issued_at);
    }

    public function test_note_number_follows_sales_not_order_ids(): void
    {
        $this->order();
        $this->assertSame('NE-0001', $this->confirmed()->delivery_note_number);
    }

    public function test_failed_confirmation_does_not_use_a_number(): void
    {
        $order = $this->order(['stock' => 1, 'code' => 'POCO']);
        try {
            app(OrderService::class)->confirm($order, $this->seller());
        } catch (\App\Exceptions\InsufficientStock) {
        }
        $this->assertNull($order->refresh()->delivery_note_number);
        $this->assertSame('NE-0001', $this->confirmed()->delivery_note_number);
    }

    public function test_public_note_shows_sale_without_costs_or_stock(): void
    {
        $order = $this->confirmed(['code' => 'A1 SKF', 'measures' => '1 x 2 x 3 mm']);
        $this->get('/nota/'.$order->delivery_note_token)->assertOk()
            ->assertSee('NE-0001')->assertSee('Ana Pérez')->assertSee('A1 SKF')->assertSee('1 x 2 x 3 mm')->assertSee('$12')
            ->assertSee('no sustituye una factura')->assertDontSee('Recibido por')->assertDontSee('Entregado por')
            ->assertSee('name="robots" content="noindex"', false)
            ->assertDontSee('987654')->assertDontSee('9.876,54')->assertDontSee('432198');
    }

    public function test_wrong_token_is_404(): void
    {
        $this->confirmed();
        $this->get('/nota/no-existe')->assertNotFound();
        $this->get('/nota/no-existe/pdf')->assertNotFound();
    }

    public function test_pdf_downloads_with_note_number(): void
    {
        $order = $this->confirmed();
        $response = $this->get('/nota/'.$order->delivery_note_token.'/pdf')->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('NE-0001', $response->headers->get('content-disposition'));
    }

    public function test_cancelled_sale_marks_note_as_void_and_keeps_number(): void
    {
        $order = $this->confirmed();
        app(OrderService::class)->cancel($order, $this->seller());
        $this->assertSame('NE-0001', $order->refresh()->delivery_note_number);
        $this->get('/nota/'.$order->delivery_note_token)->assertSee('ANULADA');
        $this->assertSame('NE-0002', $this->confirmed()->delivery_note_number);
    }

    public function test_order_page_offers_note_actions_after_sale(): void
    {
        $order = $this->order();
        $this->actingAs($this->seller());
        $this->get("/panel/pedidos/{$order->id}")->assertDontSee('Nota de entrega NE-');
        app(OrderService::class)->confirm($order, auth()->user());
        $token = $order->refresh()->delivery_note_token;
        $this->get("/panel/pedidos/{$order->id}")
            ->assertSee('Nota de entrega NE-0001')
            ->assertSee('/nota/'.$token.'/pdf', false)
            ->assertSee('wa.me/584121234567', false)
            ->assertSee(rawurlencode(url('/nota/'.$token)), false);
    }

    public function test_notes_list_and_search(): void
    {
        $order = $this->confirmed();
        $this->order();
        $this->actingAs($this->seller());
        $this->get('/panel/notas')->assertOk()->assertSee('NE-0001')->assertSee('Ana Pérez');
        $this->get('/panel/notas?q=NE-0001')->assertSee($order->number);
        $this->get('/panel/notas?q=zzz')->assertDontSee('NE-0001');
    }

    public function test_notes_list_requires_login(): void
    {
        $this->get('/panel/notas')->assertRedirect('/panel/acceso');
    }

    public function test_counter_sale_confirms_delivers_and_issues_note_at_once(): void
    {
        $p = $this->product(['price_cents' => 600, 'stock' => 5]);
        $this->actingAs($this->seller())->post('/panel/pedidos', [
            'name' => 'Luis Rojas', 'phone' => '04145551234', 'venta_inmediata' => 1,
            'items' => [['product_id' => $p->id, 'quantity' => 2]],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertSame('NE-0001', $order->delivery_note_number);
        $this->assertSame(3, $p->refresh()->stock);
    }

    public function test_counter_sale_without_stock_keeps_order_and_explains(): void
    {
        $p = $this->product(['price_cents' => 600, 'stock' => 1, 'code' => 'POCO']);
        $this->actingAs($this->seller())->post('/panel/pedidos', [
            'name' => 'Luis Rojas', 'phone' => '04145551234', 'venta_inmediata' => 1,
            'items' => [['product_id' => $p->id, 'quantity' => 2]],
        ])->assertRedirect()->assertSessionHasErrors('order');

        $order = Order::firstOrFail();
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertNull($order->delivery_note_number);
    }

    public function test_counter_form_defaults_to_immediate_sale(): void
    {
        $this->actingAs($this->seller())->get('/panel/pedidos/nuevo')->assertSee('name="venta_inmediata"', false)->assertSee('checked', false);
    }

    public function test_note_shows_payment_status_and_bolivares(): void
    {
        \App\Models\Setting::put('usd_rate', '100');
        $order = $this->confirmed();
        $this->get('/nota/'.$order->delivery_note_token)->assertSee('Pendiente')->assertSee('Bs. 1.200,00');
    }

    public function test_note_page_escapes_customer_name(): void
    {
        $p = $this->product(['price_cents' => 600]);
        $order = app(OrderService::class)->create([['product_id' => $p->id, 'quantity' => 1]], Customer::create(['name' => '<script>x</script>', 'phone' => '04120000000']), 'web', null);
        app(OrderService::class)->confirm($order, $this->seller());
        $this->get('/nota/'.$order->refresh()->delivery_note_token)->assertDontSee('<script>x</script>', false);
    }
}
