<?php

namespace Tests\Feature;

use App\Enums\Availability;
use App\Models\Order;
use App\Models\Setting;
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class ProductAvailabilityTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    public function test_availability_states(): void
    {
        $this->assertSame(Availability::Available, $this->product(['stock' => 4])->availability());
        $this->assertSame(Availability::Low, $this->product(['stock' => 3])->availability());
        $this->assertSame(Availability::Out, $this->product(['stock' => 0])->availability());
        $this->assertSame(Availability::Ask, $this->product(['stock' => 0, 'track_stock' => false])->availability());
    }

    public function test_slug_and_search_key_are_generated(): void
    {
        $p = $this->product(['code' => '6204 2RS SKF', 'measures' => '20 x 47 x 14 mm']);
        $this->assertSame('6204-2rs-skf', $p->slug);
        $this->assertStringContainsString('6204', $p->search_key);
        $this->assertStringContainsString('2047', $p->search_key);
    }

    public function test_invalid_rate_is_null(): void
    {
        Setting::put('usd_rate', '0');
        $this->assertNull(Setting::usdRate());
        Setting::put('usd_rate', 'abc');
        $this->assertNull(Setting::usdRate());
        Setting::put('usd_rate', '186.42');
        $this->assertSame('186.42', Setting::usdRate());
    }

    public function test_whatsapp_url_encodes_text_with_configured_number(): void
    {
        Setting::put('whatsapp_number', '584129477195');
        $url = WhatsApp::url('Hola ñ');
        $this->assertStringStartsWith('https://wa.me/584129477195?text=', $url);
        $this->assertStringContainsString(rawurlencode('Hola ñ'), $url);
    }

    public function test_order_total_is_null_with_unpriced_line(): void
    {
        $product = $this->product();
        $order = Order::create(['status' => 'new', 'source' => 'web']);
        $order->items()->create(['product_id' => $product->id, 'code' => 'A', 'quantity' => 2, 'unit_price_cents' => 500]);
        $this->assertSame(1000, $order->fresh()->totalCents());
        $order->items()->create(['product_id' => $product->id, 'code' => 'B', 'quantity' => 1]);
        $this->assertNull($order->fresh()->totalCents());
    }
}
