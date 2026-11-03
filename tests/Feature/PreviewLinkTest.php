<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class PreviewLinkTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['gdm.portal_private' => true, 'gdm.preview_code_hash' => hash('sha256', 'codigo-familia')]);
        $this->product(['code' => 'VISIBLE1', 'price_cents' => 1234]);
    }

    public function test_preview_link_sets_cookie_and_cleans_address(): void
    {
        $this->get('/?vista=codigo-familia')->assertRedirect('/')->assertCookie('gdm_vista');
    }

    public function test_preview_cookie_shows_full_portal(): void
    {
        $this->withCookie('gdm_vista', '1')->get('/catalogo')->assertOk()->assertSee('VISIBLE1')->assertDontSee('Próximamente');
    }

    public function test_wrong_code_stays_closed(): void
    {
        $this->get('/catalogo?vista=otro')->assertOk()->assertSee('Próximamente')->assertDontSee('VISIBLE1')->assertCookieMissing('gdm_vista');
    }

    public function test_visitor_without_link_stays_closed(): void
    {
        $this->get('/catalogo')->assertSee('Próximamente');
    }

    public function test_preview_visitor_can_use_cart_summary(): void
    {
        $p = $this->product(['code' => 'CARRO', 'price_cents' => 600]);
        $this->withCredentials()->withCookie('gdm_vista', '1')->postJson('/carrito/resumen', ['items' => [['product_id' => $p->id, 'quantity' => 1]]])->assertOk();
    }

    public function test_preview_visitor_cannot_send_real_orders(): void
    {
        $p = $this->product(['code' => 'CARRO', 'price_cents' => 600]);
        $this->withCredentials()->withCookie('gdm_vista', '1')->postJson('/pedido', [
            'name' => 'Familiar', 'phone' => '04121234567', 'token' => (string) \Illuminate\Support\Str::uuid(),
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
        ])->assertStatus(422)->assertSee('vista previa');
        $this->assertSame(0, Order::count());
    }

    public function test_preview_shows_notice_and_stays_out_of_search_engines(): void
    {
        $this->withCookie('gdm_vista', '1')->get('/')->assertSee('Vista previa')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_preview_link_does_nothing_when_portal_is_open(): void
    {
        config(['gdm.portal_private' => false]);
        $this->get('/catalogo')->assertSee('VISIBLE1')->assertDontSee('Vista previa');
    }

    public function test_preview_is_closed_without_configured_code(): void
    {
        config(['gdm.preview_code_hash' => null]);
        $this->get('/catalogo?vista=codigo-familia')->assertSee('Próximamente');
    }

    public function test_deployment_stores_only_the_code_hash(): void
    {
        $yaml = file_get_contents(base_path('app.yaml'));
        $this->assertStringContainsString('PREVIEW_CODE_HASH:', $yaml);
        $this->assertStringNotContainsString('PREVIEW_CODE:', $yaml);
    }
}
