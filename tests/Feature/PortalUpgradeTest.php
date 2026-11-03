<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\Measures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class PortalUpgradeTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product(['code' => '6204 2RS SKF', 'measures' => '20 x 47 x 14 mm', 'is_featured' => true]);
        $this->product(['code' => '30204 SKF', 'measures' => '20 x 47 x 15.25 mm'], 'Cónicos y kits');
        $this->product(['code' => 'UC207 SKF', 'measures' => '35 x 72 x 42.9 mm'], 'Agrícolas e industriales');
        $this->product(['code' => 'CRUCETA 330', 'measures' => '30 x 106 mm (dado x largo)'], 'Transmisión y sellado');
    }

    public function test_home_has_no_category_list(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Busca por categoría')->assertDontSee('Agrícolas e industriales');
    }

    public function test_home_has_measure_finder(): void
    {
        $this->get('/')->assertSee('name="interior"', false)->assertSee('name="exterior"', false)->assertSee('name="ancho"', false);
    }

    public function test_search_by_exact_measures(): void
    {
        $this->get('/catalogo?interior=20&exterior=47')->assertSee('6204 2RS SKF')->assertSee('30204 SKF')->assertDontSee('UC207 SKF');
        $this->get('/catalogo?interior=20&exterior=47&ancho=14')->assertSee('6204 2RS SKF')->assertDontSee('30204 SKF');
        $this->get('/catalogo?ancho=15,25')->assertSee('30204 SKF')->assertDontSee('6204 2RS SKF');
    }

    public function test_measure_filter_ignores_two_part_measures(): void
    {
        $this->get('/catalogo?interior=30')->assertDontSee('CRUCETA 330');
    }

    public function test_invalid_measure_is_ignored(): void
    {
        $this->get('/catalogo?interior=abc')->assertOk()->assertSee('6204 2RS SKF');
    }

    public function test_no_measure_result_offers_whatsapp_with_the_measure(): void
    {
        $this->get('/catalogo?interior=99&exterior=120')->assertSee('wa.me/584129477195', false)->assertSee(rawurlencode('99 x 120'), false);
    }

    public function test_array_query_parameters_do_not_crash(): void
    {
        $this->get('/catalogo?q[]=x&interior[]=1&categoria[]=y')->assertOk();
    }

    public function test_catalog_shows_result_count_and_category_chips(): void
    {
        $this->get('/catalogo?q=20x47')->assertSee('2 productos')->assertSee('categoria=conicos-y-kits', false);
        $this->get('/catalogo?q=uc207')->assertSee('1 producto');
    }

    public function test_category_chip_keeps_search(): void
    {
        $this->get('/catalogo?q=20x47')->assertSee('categoria=conicos-y-kits&amp;q=20x47', false);
    }

    public function test_measures_are_labeled(): void
    {
        $this->assertSame([['Diámetro interior', '20 mm'], ['Diámetro exterior', '47 mm'], ['Ancho', '15.25 mm']], Measures::labeled('20 x 47 x 15.25 mm'));
        $this->assertSame([['Dado', '30 mm'], ['Largo', '106 mm']], Measures::labeled('30 x 106 mm (dado x largo)'));
        $this->assertSame([], Measures::labeled(null));
    }

    public function test_product_page_has_specs_quantity_and_breadcrumb(): void
    {
        $this->get('/catalogo/6204-2rs-skf')
            ->assertSee('Diámetro interior')->assertSee('47 mm')
            ->assertSee('data-qty-input', false)
            ->assertSeeInOrder(['Catálogo', 'Rígidos 2RS', '6204 2RS SKF']);
    }

    public function test_guide_explains_measures_and_codes(): void
    {
        $this->get('/guia')->assertOk()->assertSee('Diámetro interior')->assertSee('2RS')->assertSee('6204')->assertSee('<svg', false);
    }

    public function test_layout_has_contact_bar_floating_whatsapp_and_guide_link(): void
    {
        $this->get('/')->assertSee('0412-947-7195')->assertSee('whatsapp-float', false)->assertSee('/guia', false);
    }

    public function test_rate_shows_in_top_bar_only_when_set(): void
    {
        $this->get('/')->assertDontSee('Tasa');
        Setting::put('usd_rate', '186.42');
        $this->get('/')->assertSee('Tasa')->assertSee('186,42');
    }

    public function test_layout_never_mentions_skf_on_non_bearing_product(): void
    {
        $this->get('/catalogo/cruceta-330')->assertOk()->assertDontSee('SKF');
    }

    public function test_panel_marks_current_section(): void
    {
        $this->actingAs($this->admin())->get('/panel/pedidos')->assertSee('aria-current="page"', false);
    }
}
