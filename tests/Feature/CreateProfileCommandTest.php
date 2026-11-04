<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateProfileCommandTest extends TestCase
{
    use RefreshDatabase;

    private function create(array $arguments, string $password = 'una-clave-larga'): \Illuminate\Testing\PendingCommand
    {
        return $this->artisan('gdm:create-admin', $arguments)
            ->expectsQuestion('Nombre', 'Prueba')
            ->expectsQuestion('Contraseña (mínimo 10 caracteres)', $password);
    }

    public function test_creates_an_admin_by_default(): void
    {
        $this->create(['email' => 'admin@gdm.test'])->expectsOutput('Administrador creado.')->assertSuccessful();

        $user = User::where('email', 'admin@gdm.test')->firstOrFail();
        $this->assertSame(Role::Admin, $user->role);
        $this->assertTrue(Hash::check('una-clave-larga', $user->password));
    }

    public function test_creates_a_seller_when_asked(): void
    {
        $this->create(['email' => 'ventas@gdm.test', '--seller' => true])->expectsOutput('Vendedor creado.')->assertSuccessful();

        $this->assertSame(Role::Seller, User::where('email', 'ventas@gdm.test')->firstOrFail()->role);
    }

    public function test_short_password_creates_no_profile(): void
    {
        $this->create(['email' => 'ventas@gdm.test', '--seller' => true], 'corta')->assertFailed();

        $this->assertSame(0, User::count());
    }
}
