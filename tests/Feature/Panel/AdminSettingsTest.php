<?php

namespace Tests\Feature\Panel;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    private function settings(array $extra = []): array
    {
        return $extra + [
            'business_name' => 'Rodamientos GDM',
            'whatsapp_number' => '584129477195',
            'city' => 'Valle de la Pascua, Guárico',
            'usd_rate' => '186.42',
        ];
    }

    public function test_seller_denied(): void
    {
        $this->actingAs($this->seller());
        $this->get('/panel/usuarios')->assertForbidden();
        $this->get('/panel/ajustes')->assertForbidden();
        $this->put('/panel/ajustes', $this->settings())->assertForbidden();
    }

    public function test_create_seller_user(): void
    {
        $this->actingAs($this->admin())->post('/panel/usuarios', [
            'name' => 'Vendedor', 'email' => 'v@example.com', 'password' => 'clave-segura-123', 'role' => 'seller',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(Role::Seller, User::where('email', 'v@example.com')->first()->role);
    }

    public function test_user_password_minimum(): void
    {
        $this->actingAs($this->admin())->post('/panel/usuarios', [
            'name' => 'V', 'email' => 'v@example.com', 'password' => 'corta', 'role' => 'seller',
        ])->assertSessionHasErrors('password');
    }

    public function test_deactivated_user_session_is_rejected(): void
    {
        $seller = $this->seller();
        $this->actingAs($seller)->get('/panel')->assertOk();
        $seller->update(['is_active' => false]);
        $this->actingAs($seller->fresh())->get('/panel')->assertRedirect('/panel/acceso');
    }

    public function test_last_admin_cannot_be_deactivated_or_demoted(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $this->put("/panel/usuarios/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'role' => 'seller', 'is_active' => 1])->assertSessionHasErrors('role');
        $this->put("/panel/usuarios/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'role' => 'admin'])->assertSessionHasErrors('role');
        $admin->refresh();
        $this->assertSame(Role::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
    }

    public function test_admin_can_be_demoted_when_another_exists(): void
    {
        $this->admin();
        $other = $this->admin();
        $this->actingAs($this->admin())->put("/panel/usuarios/{$other->id}", ['name' => 'X', 'email' => $other->email, 'role' => 'seller', 'is_active' => 1])->assertSessionHasNoErrors();
        $this->assertSame(Role::Seller, $other->refresh()->role);
    }

    public function test_password_change_is_optional_on_update(): void
    {
        $seller = $this->seller();
        $hash = $seller->password;
        $this->actingAs($this->admin())->put("/panel/usuarios/{$seller->id}", ['name' => 'X', 'email' => $seller->email, 'role' => 'seller', 'is_active' => 1, 'password' => '']);
        $this->assertSame($hash, $seller->refresh()->password);
    }

    public function test_rate_must_be_positive(): void
    {
        $this->actingAs($this->admin());
        $this->put('/panel/ajustes', $this->settings(['usd_rate' => '0']))->assertSessionHasErrors('usd_rate');
        $this->put('/panel/ajustes', $this->settings(['usd_rate' => 'abc']))->assertSessionHasErrors('usd_rate');
        $this->put('/panel/ajustes', $this->settings(['usd_rate' => '186.42']))->assertSessionHasNoErrors();
        $this->assertSame('186.42', Setting::usdRate());
        $this->put('/panel/ajustes', $this->settings(['usd_rate' => '']))->assertSessionHasNoErrors();
        $this->assertNull(Setting::usdRate());
    }

    public function test_whatsapp_must_be_international_digits(): void
    {
        $this->actingAs($this->admin())->put('/panel/ajustes', $this->settings(['whatsapp_number' => '0412-947-7195']))->assertSessionHasErrors('whatsapp_number');
    }

    public function test_optional_fields_appear_only_when_filled(): void
    {
        $this->get('/empresa')->assertOk()->assertDontSee('RIF');
        $this->actingAs($this->admin())->put('/panel/ajustes', $this->settings(['rif' => 'J-12345678-9', 'hours' => 'Lunes a viernes', 'address' => 'Calle 1', 'payment_methods' => 'Pago móvil']));
        $this->get('/empresa')->assertSee('RIF')->assertSee('J-12345678-9')->assertSee('Lunes a viernes')->assertSee('Calle 1')->assertSee('Pago móvil');
    }

    public function test_home_text_setting_is_used(): void
    {
        Setting::put('home_text', 'Texto de bienvenida especial');
        $this->get('/')->assertSee('Texto de bienvenida especial');
    }
}
