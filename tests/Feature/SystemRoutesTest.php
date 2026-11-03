<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SystemRoutesTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $extra = []): array
    {
        return $extra + [
            'codigo' => 'codigo-unico',
            'email' => 'dueno@example.com',
            'password' => 'clave-segura-123',
            'password_confirmation' => 'clave-segura-123',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['gdm.install_code_hash' => hash('sha256', 'codigo-unico'), 'gdm.admin_email' => null, 'gdm.admin_password' => null]);
    }

    public function test_status_reports_only_safe_flags(): void
    {
        config(['app.key' => 'base64:SECRETOSECRETOSECRETO']);
        $response = $this->get('/estado')->assertOk()->assertJsonStructure([
            'portal', 'php', 'app_key_set', 'install_code_set', 'session_driver', 'db_connection', 'db_host_set', 'db_name_set', 'db_reachable', 'tables_ready', 'installed', 'products',
        ]);
        $this->assertTrue($response->json('app_key_set'));
        $response->assertDontSee('SECRETOSECRETO')->assertDontSee('codigo-unico');
    }

    public function test_status_works_without_sessions_table(): void
    {
        config(['session.driver' => 'database']);
        Schema::dropIfExists('sessions');
        $this->get('/estado')->assertOk()->assertJsonPath('tables_ready', false);
    }

    public function test_installer_form_works_without_sessions_table(): void
    {
        config(['session.driver' => 'database']);
        Schema::dropIfExists('sessions');
        $this->get('/instalar')->assertOk()->assertSee('name="codigo"', false)->assertSee('name="password"', false);
    }

    public function test_installer_rejects_wrong_code(): void
    {
        $this->post('/instalar', $this->form(['codigo' => 'incorrecto']))->assertStatus(403);
        $this->assertSame(0, Product::count());
    }

    public function test_installer_requires_valid_admin_data(): void
    {
        $this->post('/instalar', $this->form(['password' => 'corta', 'password_confirmation' => 'corta']))->assertStatus(422)->assertSee('10 caracteres');
        $this->post('/instalar', $this->form(['password_confirmation' => 'distinta-123456']))->assertStatus(422);
        $this->post('/instalar', $this->form(['email' => 'no-es-correo']))->assertStatus(422);
        $this->assertSame(0, Product::count());
    }

    public function test_installer_sets_up_and_closes(): void
    {
        $this->post('/instalar', $this->form())->assertOk()->assertSee('Instalación completa')->assertDontSee('clave-segura-123');

        $user = User::firstOrFail();
        $this->assertSame('dueno@example.com', $user->email);
        $this->assertSame(Role::Admin, $user->role);
        $this->assertTrue(Hash::check('clave-segura-123', $user->password));
        $this->assertSame(18, Product::count());

        $this->get('/instalar')->assertNotFound();
        $this->post('/instalar', $this->form(['email' => 'otro@example.com']))->assertNotFound();
        $this->assertSame(1, User::count());
    }

    public function test_installer_is_closed_without_code(): void
    {
        config(['gdm.install_code_hash' => null]);
        $this->get('/instalar')->assertNotFound();
        $this->post('/instalar', $this->form())->assertNotFound();
    }

    public function test_deployment_file_needs_no_manual_secrets(): void
    {
        $yaml = file_get_contents(base_path('app.yaml'));
        foreach (['APP_URL: https://rodamientos-gdm.wasmer.app', 'INSTALL_CODE_HASH:', 'engine: mysql'] as $line) {
            $this->assertStringContainsString($line, $yaml);
        }
        foreach (['APP_KEY', 'ADMIN_PASSWORD', 'DB_PASSWORD', 'INSTALL_CODE:'] as $secret) {
            $this->assertStringNotContainsString($secret, $yaml);
        }
    }
}
