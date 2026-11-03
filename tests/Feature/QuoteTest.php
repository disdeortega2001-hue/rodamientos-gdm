<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Quote;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    private function quote(array $attrs = []): Quote
    {
        $p = $this->product($attrs + ['code' => 'A1', 'measures' => '1 x 2', 'price_cents' => 600, 'avg_cost_cents' => 987654, 'stock' => 4321]);

        return app(QuoteService::class)->create([['product_id' => $p->id, 'quantity' => 2]], $this->customer(), 'Nota');
    }

    public function test_create_quote_number_and_prices(): void
    {
        $quote = $this->quote();
        $this->assertSame('COT-0001', $quote->number);
        $this->assertSame(40, strlen($quote->public_token));
        $this->assertSame(1200, $quote->totalCents());
        $this->assertSame(4321, $quote->items->first()->product->stock);
    }

    public function test_public_link_shows_quote_without_cost(): void
    {
        $quote = $this->quote();
        $this->get("/cotizacion/{$quote->public_token}")->assertOk()
            ->assertSee('COT-0001')->assertSee('$12')->assertSee('Ana Pérez')
            ->assertDontSee('987654')->assertDontSee('9.876')->assertDontSee('4321');
    }

    public function test_wrong_token_is_404(): void
    {
        $this->quote();
        $this->get('/cotizacion/noexiste')->assertNotFound();
    }

    public function test_pdf_downloads(): void
    {
        $quote = $this->quote();
        $response = $this->get("/cotizacion/{$quote->public_token}/pdf")->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_whatsapp_link_contains_public_url(): void
    {
        $quote = $this->quote();
        $this->actingAs($this->seller())->get("/panel/cotizaciones/{$quote->id}")->assertOk()
            ->assertSee('wa.me/584121234567', false)
            ->assertSee(rawurlencode(url("/cotizacion/{$quote->public_token}")), false);
    }

    public function test_convert_creates_order_once(): void
    {
        $quote = $this->quote();
        $this->actingAs($this->seller());
        $this->post("/panel/cotizaciones/{$quote->id}/convertir")->assertRedirect();
        $this->post("/panel/cotizaciones/{$quote->id}/convertir")->assertRedirect();
        $this->assertSame(1, Order::count());
        $order = Order::first();
        $this->assertSame($order->id, $quote->refresh()->order_id);
        $this->assertSame('quote', $order->source);
        $this->assertSame(600, $order->items->first()->unit_price_cents);
    }

    public function test_convert_keeps_quoted_price_after_product_change(): void
    {
        $quote = $this->quote();
        $quote->items->first()->product->update(['price_cents' => 999]);
        $order = app(QuoteService::class)->convert($quote, $this->seller());
        $this->assertSame(600, $order->items->first()->unit_price_cents);
    }

    public function test_seller_can_create_quote(): void
    {
        $p = $this->product(['price_cents' => 600]);
        $this->actingAs($this->seller())->post('/panel/cotizaciones', [
            'name' => 'Luis Rojas', 'phone' => '0414-555-1234',
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
        ])->assertRedirect();
        $this->assertSame(1, Quote::count());
        $this->get('/panel/cotizaciones')->assertOk()->assertSee('COT-0001');
    }

    public function test_seller_page_has_no_cost(): void
    {
        $quote = $this->quote();
        $this->actingAs($this->seller())->get("/panel/cotizaciones/{$quote->id}")->assertDontSee('9.876');
    }
}
