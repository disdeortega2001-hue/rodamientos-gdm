<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function publicUrls(): array
    {
        return ['/', '/catalogo', '/empresa', '/carrito', '/creditos', '/catalogo/6204-2rs-skf'];
    }

    public function test_home_has_categories_featured_and_whatsapp(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('6204 2RS SKF')
            ->assertSee('wa.me/584129477195', false)
            ->assertSee('Cómo comprar')->assertSee('¿No sabes tu código?');
    }

    public function test_home_mentions_rolineras_and_city(): void
    {
        $this->get('/')->assertSee('rolineras')->assertSee('Valle de la Pascua');
    }

    public function test_meta_description_exists_on_public_pages(): void
    {
        foreach (['/', '/catalogo', '/catalogo/uc207-skf', '/empresa'] as $url) {
            $this->get($url)->assertSee('<meta name="description"', false);
        }
    }

    public function test_no_authorized_distributor_claim(): void
    {
        foreach ($this->publicUrls() as $url) {
            $this->get($url)->assertOk()->assertDontSee('distribuidor autorizado')->assertDontSee('Distribuidor autorizado');
        }
    }

    public function test_home_cover_uses_catalog_photo_with_alt_text(): void
    {
        $this->get('/')->assertSee('img/site/portada.webp', false)->assertSee('alt="Rolinera de bolas', false)->assertDontSee('img/site/hero.webp', false);
        $this->assertFileExists(public_path('img/site/portada.webp'));
    }

    public function test_comparison_page_is_not_shipped(): void
    {
        $this->assertFileDoesNotExist(public_path('portadas.html'));
    }

    public function test_panel_login_link_in_footer(): void
    {
        $this->get('/')->assertSee('/panel/acceso', false);
    }

    public function test_credits_list_every_internet_image(): void
    {
        $this->get('/creditos')->assertOk()
            ->assertSee('Juandev')->assertDontSee('Martin1009')
            ->assertSee('CC BY-SA 3.0')
            ->assertSee('https://commons.wikimedia.org/wiki/File:Gufero_s_prachovkou_(001).jpg', false)
            ->assertSee('https://creativecommons.org/licenses/by-sa/3.0/', false);
    }

    public function test_reference_label(): void
    {
        foreach (['6205-2rs-skf', '6306-2rs-skf', 'kit-punta-de-eje-de-rotativa', '207krrb17-skf', 'estopera-60x90x10'] as $slug) {
            $this->get("/catalogo/{$slug}")->assertSee('Imagen referencial');
        }
    }

    public function test_catalog_photo_is_not_reference(): void
    {
        $this->assertFalse(Product::where('code', '6204 2RS SKF')->first()->images->first()->is_reference);
    }

    public function test_every_product_image_file_exists(): void
    {
        Product::with('images')->get()->each(function (Product $p) {
            $this->assertNotEmpty($p->images, $p->code.' sin foto');
            $this->assertFileExists(public_path($p->images->first()->path));
        });
    }

    public function test_non_bearings_have_no_brand(): void
    {
        foreach (['cruceta-330', 'cruceta-369', 'estopera-60x90x10'] as $slug) {
            $this->get("/catalogo/{$slug}")->assertDontSee('SKF');
        }
    }

    public function test_all_bearings_show_skf(): void
    {
        Product::whereNotIn('code', ['CRUCETA 330', 'CRUCETA 369', 'ESTOPERA 60X90X10'])->get()
            ->each(fn (Product $p) => $this->get('/catalogo/'.$p->slug)->assertSee('SKF'));
        $this->assertTrue(Product::where('code', '6306 2RS SKF')->exists());
    }

    public function test_seeding_twice_does_not_duplicate_images(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(1, Product::where('code', '6204 2RS SKF')->first()->images()->count());
    }
}
