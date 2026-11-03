<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;

class CreateAdmin extends Command
{
    protected $signature = 'gdm:create-admin {email}';

    protected $description = 'Crea un usuario administrador';

    public function handle(): int
    {
        $name = $this->ask('Nombre');
        $password = (string) $this->secret('Contraseña (mínimo 10 caracteres)');

        if (mb_strlen($password) < 10) {
            $this->error('La contraseña debe tener al menos 10 caracteres.');

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $this->argument('email'),
            'password' => $password,
            'role' => Role::Admin,
            'is_active' => true,
        ]);

        $this->info('Administrador creado.');

        return self::SUCCESS;
    }
}
