<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class PrivateModeTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['gdm.portal_private' => true]);
        $this->product(['code' => 'OCULTAME', 'price_cents' => 1234]);
    }

    public function test_visitors_only_see_coming_soon(): void
    {
        foreach (['/', '/catalogo', '/catalogo/ocultame', '/guia', '/empresa', '/carrito', '/creditos'] as $url) {
            $this->get($url)->assertOk()->assertSee('Próximamente')->assertSee('noindex', false)
                ->assertDontSee('OCULTAME')->assertDontSee('$12,34');
        }
    }

    public function test_visitors_cannot_use_cart_or_send_orders(): void
    {
        $p = $this->product(['code' => 'OTRO']);
        $this->postJson('/carrito/resumen', ['items' => [['product_id' => $p->id, 'quantity' => 1]]])->assertForbidden();
        $this->postJson('/pedido', ['name' => 'Ana', 'phone' => '04121234567', 'token' => 'x', 'items' => []])->assertForbidden();
        $this->assertSame(0, Order::count());
    }

    public function test_staff_can_reach_login_from_coming_soon(): void
    {
        $this->get('/')->assertSee('/panel/acceso', false);
        $this->get('/panel/acceso')->assertOk()->assertDontSee('Próximamente');
    }

    public function test_signed_in_staff_see_the_full_portal(): void
    {
        $this->actingAs($this->seller())->get('/catalogo')->assertOk()->assertSee('OCULTAME')->assertDontSee('Próximamente');
    }

    public function test_signed_in_staff_can_test_a_web_order(): void
    {
        $p = $this->product(['code' => 'PRUEBA']);
        $this->actingAs($this->seller())->postJson('/pedido', [
            'name' => 'Ana Pérez', 'phone' => '04121234567', 'token' => (string) \Illuminate\Support\Str::uuid(),
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
        ])->assertOk();
    }

    public function test_customer_document_links_keep_working(): void
    {
        $p = $this->product(['code' => 'NOTA1']);
        $order = app(OrderService::class)->create([['product_id' => $p->id, 'quantity' => 1]], $this->customer(), 'counter', null);
        app(OrderService::class)->confirm($order, $this->seller());
        $this->get('/nota/'.$order->refresh()->delivery_note_token)->assertOk()->assertSee('NOTA1');
    }

    public function test_admin_can_open_the_portal_from_settings(): void
    {
        $base = ['business_name' => 'X', 'whatsapp_number' => '584129477195', 'city' => 'Y', 'usd_rate_auto' => 1];
        $this->actingAs($this->admin());
        $this->get('/panel/ajustes')->assertSee('name="portal_public"', false);
        $this->put('/panel/ajustes', $base + ['portal_public' => 1])->assertSessionHasNoErrors();
        $this->assertSame('1', Setting::get('portal_public'));
        auth()->logout();
        $this->get('/catalogo')->assertSee('OCULTAME');

        $this->actingAs($this->admin())->put('/panel/ajustes', $base);
        auth()->logout();
        $this->get('/catalogo')->assertDontSee('OCULTAME');
    }

    public function test_seller_cannot_change_visibility(): void
    {
        $this->actingAs($this->seller())->put('/panel/ajustes', ['portal_public' => 1])->assertForbidden();
        $this->assertNull(Setting::get('portal_public'));
    }

    public function test_deployment_starts_private(): void
    {
        $this->assertStringContainsString('PORTAL_PRIVATE: "true"', file_get_contents(base_path('app.yaml')));
    }
}
