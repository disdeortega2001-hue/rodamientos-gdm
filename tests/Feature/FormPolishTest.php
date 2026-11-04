<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class FormPolishTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    public function test_measure_finder_shows_units_inside_fields(): void
    {
        $this->get('/')->assertSee('class="input-unit"', false)->assertSee('>mm<', false);
    }

    public function test_cart_lines_have_photo_and_quantity_buttons(): void
    {
        $script = file_get_contents(public_path('js/cart.js'));
        $this->assertStringContainsString('cart-thumb', $script);
        $this->assertStringContainsString('Quitar uno', $script);
        $this->assertStringContainsString('Agregar uno', $script);
    }

    public function test_public_pages_load_motion_script(): void
    {
        $this->get('/')->assertSee('js/motion.js', false);
    }

    public function test_motion_respects_reduced_motion_preference(): void
    {
        $this->assertStringContainsString('prefers-reduced-motion', file_get_contents(public_path('js/motion.js')));
        $this->assertStringContainsString('.reveal { opacity: 1 !important; transform: none !important; }', file_get_contents(public_path('css/app.css')));
    }

    public function test_add_to_cart_gives_animated_feedback(): void
    {
        $script = file_get_contents(public_path('js/cart.js'));
        $this->assertStringContainsString('is-added', $script);
        $this->assertStringContainsString('cart-bump', $script);
        $this->assertStringContainsString('cart-list-enter', $script);
    }

    public function test_settings_are_grouped_in_sections(): void
    {
        $this->actingAs($this->admin())->get('/panel/ajustes')
            ->assertSeeInOrder(['Tasa del dólar', 'Datos del negocio', 'Contacto', 'Texto del inicio']);
    }

    public function test_order_form_lets_remove_a_line(): void
    {
        $this->product();
        $this->actingAs($this->seller())->get('/panel/pedidos/nuevo')->assertSee('data-remove-line', false)->assertSee('choice', false);
    }

    public function test_product_list_uses_status_pill_and_edit_button(): void
    {
        $this->product();
        $this->actingAs($this->admin())->get('/panel/productos')->assertSee('pill-ok', false)->assertSee('btn btn-sm btn-outline-brand', false);
    }
}
