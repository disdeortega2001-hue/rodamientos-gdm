<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SystemController extends Controller
{
    public function installForm(): Response
    {
        abort_unless($this->installerOpen(), 404);

        return response()->view('system.install');
    }

    public function install(Request $request): Response
    {
        abort_unless($this->installerOpen(), 404);

        if (! hash_equals((string) config('gdm.install_code_hash'), hash('sha256', trim((string) $request->input('codigo', ''))))) {
            return response()->view('system.install', ['error' => 'El código de instalación no es correcto.', 'email' => $request->input('email')], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ], [
            'email.required' => 'Escribe tu correo.',
            'email.email' => 'Escribe un correo válido.',
            'password.required' => 'Escribe una contraseña.',
            'password.min' => 'La contraseña debe tener al menos 10 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if ($validator->fails()) {
            return response()->view('system.install', ['error' => $validator->errors()->first(), 'email' => $request->input('email')], 422);
        }

        try {
            $code = Artisan::call('gdm:setup');

            if ($code !== 0) {
                return response()->view('system.install', ['error' => 'No se pudo instalar: '.trim(Artisan::output())], 500);
            }

            if (! User::exists()) {
                User::create([
                    'name' => 'Administrador',
                    'email' => $request->input('email'),
                    'password' => $request->input('password'),
                    'role' => Role::Admin,
                    'is_active' => true,
                ]);
            }
        } catch (Throwable $e) {
            return response()->view('system.install', ['error' => 'No se pudo instalar: '.$e->getMessage()], 500);
        }

        return response()->view('system.installed');
    }

    private function installerOpen(): bool
    {
        if (! config('gdm.install_code_hash')) {
            return false;
        }

        try {
            return ! (Schema::hasTable('users') && User::exists());
        } catch (Throwable) {
            return true;
        }
    }
}
