<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\CreatesCatalog;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    private function payload(array $items, array $extra = []): array
    {
        return $extra + [
            'name' => 'Ana Pérez',
            'phone' => '0412-123-4567',
            'token' => (string) Str::uuid(),
            'items' => $items,
        ];
    }

    private function message(array $json): string
    {
        return rawurldecode(explode('?text=', $json['whatsapp_url'])[1]);
    }

    public function test_checkout_creates_order_and_returns_message(): void
    {
        Setting::put('whatsapp_number', '584129477195');
        $a = $this->product(['code' => '6204 2RS SKF', 'measures' => '20 x 47 x 14 mm', 'price_cents' => 600]);
        $b = $this->product(['code' => 'UC207 SKF', 'measures' => '35 x 72 x 42.9 mm', 'price_cents' => 2500]);

        $json = $this->postJson('/pedido', $this->payload([
            ['product_id' => $a->id, 'quantity' => 2],
            ['product_id' => $b->id, 'quantity' => 1],
        ]))->assertOk()->assertJsonPath('number', 'GDM-0001')->json();

        $this->assertStringStartsWith('https://wa.me/584129477195?text=', $json['whatsapp_url']);
        $this->assertSame(
            "Hola, Rodamiento GDM. Soy Ana Pérez. Mi pedido es GDM-0001:\n\n"
            ."- 2 x 6204 2RS SKF (20 x 47 x 14 mm): $12\n"
            ."- 1 x UC207 SKF (35 x 72 x 42.9 mm): $25\n\n"
            ."Total estimado: $37\n¿Tienen disponibilidad?",
            $this->message($json)
        );
        $this->assertSame('web', Order::first()->source);
    }

    public function test_checkout_ignores_client_price(): void
    {
        $p = $this->product(['price_cents' => 600]);
        $this->postJson('/pedido', $this->payload([['product_id' => $p->id, 'quantity' => 1, 'price' => 1, 'unit_price_cents' => 1]]))->assertOk();
        $this->assertSame(600, Order::first()->items->first()->unit_price_cents);
    }

    public function test_hidden_product_rejected(): void
    {
        $p = $this->product(['is_visible' => false]);
        $this->postJson('/pedido', $this->payload([['product_id' => $p->id, 'quantity' => 1]]))->assertStatus(422);
        $this->assertSame(0, Order::count());
    }

    public function test_same_token_creates_one_order(): void
    {
        $p = $this->product();
        $payload = $this->payload([['product_id' => $p->id, 'quantity' => 1]]);
        $first = $this->postJson('/pedido', $payload)->json('number');
        $second = $this->postJson('/pedido', $payload)->json('number');
        $this->assertSame($first, $second);
        $this->assertSame(1, Order::count());
    }

    public function test_same_phone_reuses_customer(): void
    {
        $p = $this->product();
        $items = [['product_id' => $p->id, 'quantity' => 1]];
        $this->postJson('/pedido', $this->payload($items, ['phone' => '0412-947-7195']))->assertOk();
        $this->postJson('/pedido', $this->payload($items, ['phone' => '+58 412 947 7195']))->assertOk();
        $this->assertSame(1, Customer::count());
        $this->assertSame('04129477195', Customer::first()->phone);
    }

    public function test_different_name_is_kept_on_order_note(): void
    {
        $p = $this->product();
        $items = [['product_id' => $p->id, 'quantity' => 1]];
        $this->postJson('/pedido', $this->payload($items));
        $json = $this->postJson('/pedido', $this->payload($items, ['name' => 'Luis Rojas']))->json();
        $this->assertSame('Ana Pérez', Customer::first()->name);
        $this->assertStringContainsString('Luis Rojas', Order::latest('id')->first()->note);
        $this->assertStringContainsString('Soy Luis Rojas', $this->message($json));
    }

    public function test_null_price_message(): void
    {
        $p = $this->product(['code' => 'KIT', 'measures' => null, 'price_cents' => null]);
        $message = $this->message($this->postJson('/pedido', $this->payload([['product_id' => $p->id, 'quantity' => 1]]))->json());
        $this->assertStringContainsString('- 1 x KIT: Consultar precio', $message);
        $this->assertStringContainsString('Total estimado: por confirmar', $message);
    }

    public function test_empty_cart_rejected(): void
    {
        $this->postJson('/pedido', $this->payload([]))->assertStatus(422);
    }

    public function test_quantity_limits(): void
    {
        $p = $this->product();
        $this->postJson('/pedido', $this->payload([['product_id' => $p->id, 'quantity' => 0]]))->assertStatus(422);
        $this->postJson('/pedido', $this->payload([['product_id' => $p->id, 'quantity' => 1000]]))->assertStatus(422);
    }

    public function test_invalid_phone_rejected(): void
    {
        $p = $this->product();
        $this->postJson('/pedido', $this->payload([['product_id' => $p->id, 'quantity' => 1]], ['phone' => '123']))
            ->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_rate_limit(): void
    {
        $p = $this->product();
        foreach (range(1, 10) as $i) {
            $this->postJson('/pedido', $this->payload([['product_id' => $p->id, 'quantity' => 1]]))->assertOk();
        }
        $this->postJson('/pedido', $this->payload([['product_id' => $p->id, 'quantity' => 1]]))->assertStatus(429);
    }

    public function test_response_has_no_private_fields(): void
    {
        $p = $this->product(['avg_cost_cents' => 987654, 'stock' => 4321]);
        $this->postJson('/pedido', $this->payload([['product_id' => $p->id, 'quantity' => 1]]))
            ->assertOk()->assertDontSee('987654')->assertDontSee('4321');
    }

    public function test_cart_summary_uses_current_price_and_flags_missing(): void
    {
        $p = $this->product(['price_cents' => 900, 'avg_cost_cents' => 987654, 'stock' => 4321]);
        $hidden = $this->product(['is_visible' => false]);

        $this->postJson('/carrito/resumen', ['items' => [
            ['product_id' => $p->id, 'quantity' => 2, 'price_cents' => 1],
            ['product_id' => $hidden->id, 'quantity' => 1],
            ['product_id' => 99999, 'quantity' => 1],
        ]])->assertOk()
            ->assertJsonPath('items.0.line_text', '$18')
            ->assertJsonPath('total_text', '$18')
            ->assertJsonPath('missing', [$hidden->id, 99999])
            ->assertDontSee('987654')->assertDontSee('4321');
    }

    public function test_cart_page_loads(): void
    {
        $this->get('/carrito')->assertOk()->assertSee('Mi pedido');
    }
}
