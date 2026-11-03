<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;

class Setup extends Command
{
    protected $signature = 'gdm:setup';

    protected $description = 'Prepara el portal al publicar: migraciones, catálogo inicial y primer administrador';

    public function handle(): int
    {
        $this->call('migrate', ['--force' => true]);

        if (Product::count() === 0) {
            $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);
        }

        $email = config('gdm.admin_email');
        $password = (string) config('gdm.admin_password');

        if (User::exists() || ! $email || $password === '') {
            return self::SUCCESS;
        }

        if (mb_strlen($password) < 10) {
            $this->error('ADMIN_PASSWORD debe tener al menos 10 caracteres.');

            return self::FAILURE;
        }

        User::create(['name' => 'Administrador', 'email' => $email, 'password' => $password, 'role' => Role::Admin, 'is_active' => true]);
        $this->info('Administrador creado.');

        return self::SUCCESS;
    }
}
