<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_page_offers_pago_movil_by_default(): void
    {
        $this->get('/empresa')->assertOk()->assertSeeInOrder(['Formas de pago', 'Pago móvil']);
    }

    public function test_admin_can_replace_the_payment_methods(): void
    {
        Setting::put('payment_methods', 'Efectivo');
        $this->get('/empresa')->assertSee('Efectivo')->assertDontSee('Pago móvil');
    }

    public function test_home_shows_the_skf_logo_without_claiming_authorization(): void
    {
        $this->assertFileExists(public_path('img/site/skf.svg'));
        $this->get('/')->assertOk()->assertSee('img/site/skf.svg', false)->assertSee('alt="Logo de SKF"', false)->assertDontSee('distribuidor autorizado');
    }

    public function test_credits_explain_the_skf_trademark(): void
    {
        $this->get('/creditos')->assertOk()->assertSee('SKF y su logo son marcas de su titular');
    }

    public function test_company_page_shows_no_rif_until_one_is_saved(): void
    {
        $this->get('/empresa')->assertOk()->assertDontSee('RIF');
    }
}
