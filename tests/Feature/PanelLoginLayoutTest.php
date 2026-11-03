<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class PanelLoginLayoutTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    public function test_login_page_does_not_use_the_sidebar_grid(): void
    {
        $this->get('/panel/acceso')->assertOk()
            ->assertDontSee('panel-with-nav', false)
            ->assertSee('brand-mark', false);
    }

    public function test_signed_in_panel_uses_the_sidebar_grid(): void
    {
        $this->actingAs($this->admin())->get('/panel')->assertSee('panel-with-nav', false);
    }

    public function test_sidebar_grid_is_not_applied_to_every_panel_page(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        $this->assertStringNotContainsString('.panel-body { display: grid', $css);
    }
}
