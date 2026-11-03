<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product(['code' => '6204 2RS SKF', 'measures' => '20 x 47 x 14 mm', 'description' => 'Rodamiento rígido de bolas']);
        $this->product(['code' => '30204 SKF', 'measures' => '20 x 47 x 15.25 mm', 'description' => 'Rodamiento de rodillos cónicos'], 'Cónicos y kits');
        $this->product(['code' => 'UC207 SKF', 'measures' => '35 x 72 x 42.9 mm', 'description' => 'Rodamiento de inserción para chumacera', 'price_cents' => 2500], 'Agrícolas e industriales');
    }

    public function test_search_by_code(): void
    {
        $this->get('/catalogo?q=uc 207')->assertOk()->assertSee('UC207 SKF')->assertDontSee('6204 2RS SKF');
    }

    public function test_search_by_measure(): void
    {
        $this->get('/catalogo?q=20x47')->assertSee('30204 SKF')->assertSee('6204 2RS SKF')->assertDontSee('UC207 SKF');
    }

    public function test_search_ignores_accents(): void
    {
        $this->get('/catalogo?q=rodamíento')->assertSee('6204 2RS SKF');
    }

    public function test_search_by_separate_words(): void
    {
        $this->get('/catalogo?q=rodamiento conicos')->assertSee('30204 SKF')->assertDontSee('6204 2RS SKF');
    }

    public function test_no_results_offers_whatsapp(): void
    {
        $this->get('/catalogo?q=zzz')->assertSee('wa.me/584129477195', false)->assertSee(rawurlencode('zzz'), false);
    }

    public function test_filter_combines_with_search(): void
    {
        $this->get('/catalogo?q=rodamiento&categoria=rigidos-2rs')->assertSee('6204 2RS SKF')->assertDontSee('UC207 SKF');
    }

    public function test_only_available(): void
    {
        $this->product(['code' => 'AGOTADO1', 'stock' => 0]);
        $this->product(['code' => 'CONSULTA1', 'stock' => 0, 'track_stock' => false]);
        $this->get('/catalogo?disponibles=1')->assertDontSee('AGOTADO1')->assertSee('CONSULTA1');
    }

    public function test_sort_by_price_desc(): void
    {
        $this->get('/catalogo?orden=precio_desc')->assertSeeInOrder(['UC207 SKF', '6204 2RS SKF']);
    }

    public function test_hidden_product_is_404(): void
    {
        $this->product(['code' => 'OCULTO', 'is_visible' => false]);
        $this->get('/catalogo/oculto')->assertNotFound();
        $this->get('/catalogo')->assertDontSee('OCULTO');
    }

    public function test_product_page_shows_details_and_related(): void
    {
        $this->product(['code' => '6206 2RS SKF']);
        $this->get('/catalogo/6204-2rs-skf')->assertOk()
            ->assertSee('20 x 47 x 14 mm')->assertSee('$6')->assertSee('6206 2RS SKF');
    }

    public function test_null_price_shows_consultar(): void
    {
        $this->product(['code' => 'SINPRECIO', 'price_cents' => null]);
        $this->get('/catalogo/sinprecio')->assertSee('Consultar precio');
    }

    public function test_price_in_bs_only_with_rate(): void
    {
        $this->get('/catalogo')->assertDontSee('Bs.');
        Setting::put('usd_rate', '186.42');
        $this->get('/catalogo')->assertSee('Bs. 1.118,52');
    }

    public function test_low_stock_shows_as_available_without_quantity(): void
    {
        $this->product(['code' => 'POCAS', 'stock' => 2]);
        $this->get('/catalogo/pocas')->assertSee('Disponible')->assertDontSee('2 unidades');
    }

    public function test_reference_image_is_labeled(): void
    {
        $p = $this->product(['code' => 'REFER']);
        $p->images()->create(['path' => 'img/x.webp', 'is_reference' => true]);
        $this->get('/catalogo/refer')->assertSee('Imagen referencial');
    }
}
