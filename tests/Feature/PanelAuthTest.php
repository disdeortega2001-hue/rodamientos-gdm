<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\CreatesCatalog;
use Tests\TestCase;

class PanelAuthTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    public function test_panel_requires_login(): void
    {
        $this->get('/panel')->assertRedirect('/panel/acceso');
    }

    public function test_valid_login(): void
    {
        $user = $this->admin();
        $this->post('/panel/acceso', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/panel');
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_rejected(): void
    {
        $user = $this->admin();
        $this->post('/panel/acceso', ['email' => $user->email, 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->post('/panel/acceso', ['email' => $user->email, 'password' => 'password']);
        $this->assertGuest();
    }

    public function test_login_is_throttled(): void
    {
        $user = $this->admin();
        foreach (range(1, 5) as $i) {
            $this->post('/panel/acceso', ['email' => $user->email, 'password' => 'nope']);
        }
        $this->post('/panel/acceso', ['email' => $user->email, 'password' => 'nope'])->assertStatus(429);
    }

    public function test_logout(): void
    {
        $this->actingAs($this->admin())->post('/panel/salir')->assertRedirect('/panel/acceso');
        $this->assertGuest();
    }

    public function test_seller_denied_manage_all(): void
    {
        $this->actingAs($this->seller());
        $this->assertTrue(Gate::denies('manage-all'));
    }

    public function test_admin_allowed_manage_all(): void
    {
        $this->actingAs($this->admin());
        $this->assertTrue(Gate::allows('manage-all'));
    }

    public function test_no_default_admin_exists(): void
    {
        $this->seed();
        $this->assertSame(0, User::count());
    }

    public function test_create_admin_command(): void
    {
        $this->artisan('gdm:create-admin', ['email' => 'dueno@example.com'])
            ->expectsQuestion('Nombre', 'Dueño')
            ->expectsQuestion('Contraseña (mínimo 10 caracteres)', 'clave-segura-123')
            ->assertSuccessful();

        $user = User::where('email', 'dueno@example.com')->firstOrFail();
        $this->assertSame(Role::Admin, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('clave-segura-123', $user->password));
    }

    public function test_create_admin_rejects_short_password(): void
    {
        $this->artisan('gdm:create-admin', ['email' => 'dueno@example.com'])
            ->expectsQuestion('Nombre', 'Dueño')
            ->expectsQuestion('Contraseña (mínimo 10 caracteres)', 'corta')
            ->assertFailed();
    }
}
