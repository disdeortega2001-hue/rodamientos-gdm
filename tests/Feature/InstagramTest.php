<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class InstagramTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    private function settings(array $extra = []): array
    {
        return $extra + ['business_name' => 'X', 'whatsapp_number' => '584129477195', 'city' => 'Y', 'usd_rate_auto' => 1];
    }

    public function test_public_pages_link_to_instagram(): void
    {
        foreach (['/', '/empresa'] as $url) {
            $this->get($url)->assertSee('https://www.instagram.com/rodamientogdm/', false)->assertSee('@rodamientogdm');
        }
    }

    public function test_company_page_lists_instagram(): void
    {
        $this->get('/empresa')->assertSeeInOrder(['Instagram', '@rodamientogdm']);
    }

    public function test_instagram_links_are_safe(): void
    {
        $this->get('/')->assertSee('rel="noopener noreferrer"', false)->assertSee('target="_blank"', false);
    }

    public function test_admin_can_change_instagram(): void
    {
        $this->actingAs($this->admin())->put('/panel/ajustes', $this->settings(['instagram' => 'otracuenta']))->assertSessionHasNoErrors();
        $this->assertSame('otracuenta', Setting::get('instagram'));
    }

    public function test_settings_accept_handle_written_with_at_or_link(): void
    {
        $this->actingAs($this->admin());
        $this->put('/panel/ajustes', $this->settings(['instagram' => '@cuenta.uno']))->assertSessionHasNoErrors();
        $this->assertSame('cuenta.uno', Setting::get('instagram'));
        $this->put('/panel/ajustes', $this->settings(['instagram' => 'https://www.instagram.com/cuenta_dos/']))->assertSessionHasNoErrors();
        $this->assertSame('cuenta_dos', Setting::get('instagram'));
    }

    public function test_settings_reject_invalid_handle(): void
    {
        $this->actingAs($this->admin())->put('/panel/ajustes', $this->settings(['instagram' => 'no valido"><script>']))->assertSessionHasErrors('instagram');
    }

    public function test_settings_form_shows_instagram_field(): void
    {
        $this->actingAs($this->admin())->get('/panel/ajustes')->assertSee('name="instagram"', false)->assertSee('rodamientogdm');
    }
}
