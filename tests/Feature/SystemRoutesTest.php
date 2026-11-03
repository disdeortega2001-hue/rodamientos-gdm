<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SystemRoutesTest extends TestCase
{
    use RefreshDatabase;

    private function adminSecrets(): void
    {
        config(['gdm.admin_email' => 'dueno@example.com', 'gdm.admin_password' => 'clave-segura-123']);
    }

    public function test_status_reports_only_safe_flags(): void
    {
        config(['app.key' => 'base64:SECRETOSECRETOSECRETO', 'gdm.admin_password' => 'clave-segura-123', 'gdm.admin_email' => 'dueno@example.com']);
        $response = $this->get('/estado')->assertOk()->assertJsonStructure([
            'portal', 'php', 'app_key_set', 'admin_secrets_set', 'session_driver', 'db_connection', 'db_host_set', 'db_name_set', 'db_reachable', 'tables_ready', 'installed', 'products',
        ]);
        $this->assertTrue($response->json('app_key_set'));
        $response->assertDontSee('SECRETOSECRETO')->assertDontSee('clave-segura-123')->assertDontSee('dueno@example.com');
    }

    public function test_status_works_without_sessions_table(): void
    {
        config(['session.driver' => 'database']);
        Schema::dropIfExists('sessions');
        $this->get('/estado')->assertOk()->assertJsonPath('tables_ready', false);
    }

    public function test_installer_works_without_sessions_table(): void
    {
        $this->adminSecrets();
        config(['session.driver' => 'database']);
        Schema::dropIfExists('sessions');
        $this->get('/instalar')->assertOk()->assertSee('Instalar');
    }

    public function test_installer_rejects_wrong_password(): void
    {
        $this->adminSecrets();
        $this->post('/instalar', ['clave' => 'incorrecta'])->assertStatus(403);
        $this->assertSame(0, User::count());
        $this->assertSame(0, Product::count());
    }

    public function test_installer_sets_up_with_admin_password_and_closes(): void
    {
        $this->adminSecrets();
        $this->post('/instalar', ['clave' => 'clave-segura-123'])->assertOk()->assertSee('Instalación completa');
        $this->assertSame(1, User::count());
        $this->assertSame(18, Product::count());

        $this->get('/instalar')->assertNotFound();
        $this->post('/instalar', ['clave' => 'clave-segura-123'])->assertNotFound();
    }

    public function test_installer_is_unavailable_without_secrets(): void
    {
        config(['gdm.admin_email' => null, 'gdm.admin_password' => null]);
        $this->get('/instalar')->assertNotFound();
        $this->post('/instalar', ['clave' => ''])->assertNotFound();
    }
}
