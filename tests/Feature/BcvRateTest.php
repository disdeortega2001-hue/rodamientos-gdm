<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\UsdRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\CreatesCatalog;
use Tests\TestCase;

class BcvRateTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    private const BCV = 'https://bcv.test/';

    private const API = 'https://api.test/oficial';

    private const HTML = '<div id="euro"><strong class="strong-tb">999,00000000</strong></div><div id="dolar" class="col-sm-12 col-xs-12 "><span> USD</span><div class="centrado textp"> <strong class="strong-tb">891,10540000</strong>  </div></div>'
        .'<div class="pull-right dinpro center">Fecha Valor: <span class="date-display-single" property="dc:date" content="2026-11-03T00:00:00-04:00">Martes</span></div>';

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-11-03 12:00:00', 'America/Caracas'));
        config(['gdm.bcv_url' => self::BCV, 'gdm.bcv_fallback_url' => self::API]);
    }

    private function refresh(): void
    {
        app(UsdRateService::class)->refreshIfStale();
    }

    public function test_reads_official_rate_from_bcv_page(): void
    {
        Http::fake([self::BCV => Http::response(self::HTML)]);
        $this->refresh();
        $this->assertSame('891.1054', Setting::usdRate());
        Http::assertSentCount(1);
    }

    public function test_rate_with_future_value_date_waits_until_its_day(): void
    {
        Setting::put('usd_rate', '891.1054');
        $future = str_replace('2026-11-03', '2026-11-04', self::HTML);
        $future = str_replace('891,10540000', '893,21730000', $future);
        Http::fake([self::BCV => Http::response($future), self::API => Http::response(['promedio' => 891.1054])]);

        $this->refresh();
        $this->assertSame('891.1054', Setting::usdRate());

        Cache::flush();
        $this->travelTo(\Carbon\Carbon::parse('2026-11-04 08:00:00', 'America/Caracas'));
        $this->refresh();
        $this->assertSame('893.2173', Setting::usdRate());
    }

    public function test_uses_fallback_when_bcv_fails(): void
    {
        Http::fake([self::BCV => Http::response('error', 500), self::API => Http::response(['promedio' => 891.1054])]);
        $this->refresh();
        $this->assertSame('891.1054', Setting::usdRate());
    }

    public function test_keeps_last_rate_when_all_sources_fail(): void
    {
        Setting::put('usd_rate', '800');
        Http::fake(['*' => Http::response('error', 500)]);
        $this->refresh();
        $this->assertSame('800', Setting::usdRate());
    }

    public function test_ignores_page_without_valid_rate(): void
    {
        Setting::put('usd_rate', '800');
        Http::fake([self::BCV => Http::response('<div id="dolar"><strong>0,00</strong></div>'), self::API => Http::response(['promedio' => null])]);
        $this->refresh();
        $this->assertSame('800', Setting::usdRate());
    }

    public function test_does_not_fetch_again_within_six_hours(): void
    {
        Http::fake([self::BCV => Http::response(self::HTML)]);
        $this->refresh();
        Cache::flush();
        $this->refresh();
        Http::assertSentCount(1);

        $this->travelTo(now()->addHours(7));
        $this->refresh();
        Http::assertSentCount(2);
    }

    public function test_failed_attempts_wait_before_retrying(): void
    {
        Http::fake(['*' => Http::response('error', 500)]);
        $this->refresh();
        $sent = Http::recorded()->count();
        $this->refresh();
        $this->assertSame($sent, Http::recorded()->count());
    }

    public function test_manual_rate_turns_off_automatic_updates(): void
    {
        Setting::put('usd_rate_auto', '0');
        Setting::put('usd_rate', '500');
        Http::fake();
        $this->refresh();
        Http::assertNothingSent();
        $this->assertSame('500', Setting::usdRate());
    }

    public function test_page_visit_refreshes_rate_and_shows_it(): void
    {
        Http::fake([self::BCV => Http::response(self::HTML)]);
        $this->get('/')->assertOk()->assertSee('Tasa BCV')->assertSee('891,11');
    }

    public function test_network_failure_never_breaks_the_page(): void
    {
        Http::fake(fn () => throw new \RuntimeException('sin red'));
        $this->get('/')->assertOk();
    }

    public function test_settings_form_toggles_automatic_rate(): void
    {
        $base = ['business_name' => 'X', 'whatsapp_number' => '584129477195', 'city' => 'Y'];
        $this->actingAs($this->admin());
        $this->get('/panel/ajustes')->assertSee('name="usd_rate_auto"', false)->assertSee('checked', false);
        $this->put('/panel/ajustes', $base + ['usd_rate' => '500'])->assertSessionHasNoErrors();
        $this->assertSame('0', Setting::get('usd_rate_auto'));
        $this->put('/panel/ajustes', $base + ['usd_rate_auto' => 1])->assertSessionHasNoErrors();
        $this->assertSame('1', Setting::get('usd_rate_auto'));
    }

    public function test_automatic_mode_keeps_current_rate_when_form_is_blank(): void
    {
        Setting::put('usd_rate', '891.1054');
        $this->actingAs($this->admin())->put('/panel/ajustes', ['business_name' => 'X', 'whatsapp_number' => '584129477195', 'city' => 'Y', 'usd_rate_auto' => 1]);
        $this->assertSame('891.1054', Setting::usdRate());
    }

    public function test_address_and_hours_defaults_are_public(): void
    {
        $this->get('/empresa')->assertSee('Valle de la Pascua, estado Guárico')->assertSee('Siempre disponible');
    }

    public function test_customer_never_sees_stock_hints(): void
    {
        $p = $this->product(['code' => 'POQUITAS', 'stock' => 2, 'low_stock_threshold' => 3]);
        foreach (['/catalogo', '/catalogo/poquitas'] as $url) {
            $this->get($url)->assertSee('Disponible')->assertDontSee('Pocas unidades');
        }
        $this->postJson('/carrito/resumen', ['items' => [['product_id' => $p->id, 'quantity' => 1]]])->assertDontSee('Pocas unidades');
    }

    public function test_panel_still_warns_about_low_stock(): void
    {
        $this->product(['code' => 'POQUITAS', 'stock' => 2]);
        $this->actingAs($this->seller())->get('/panel/inventario')->assertSee('Pocas unidades');
    }
}
