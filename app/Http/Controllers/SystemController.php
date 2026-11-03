<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemController extends Controller
{
    /** Reports configuration as yes/no flags only; never values. */
    public function status(): JsonResponse
    {
        $connection = config('database.default');
        $reachable = false;
        $tables = false;
        $installed = false;
        $products = 0;

        try {
            DB::connection()->getPdo();
            $reachable = true;
            $tables = Schema::hasTable('sessions') && Schema::hasTable('users') && Schema::hasTable('products');
            if ($tables) {
                $installed = User::exists();
                $products = Product::count();
            }
        } catch (Throwable) {
            // reachable and tables stay false
        }

        return response()->json([
            'portal' => 'ok',
            'php' => PHP_VERSION,
            'app_key_set' => (bool) config('app.key'),
            'admin_secrets_set' => $this->secretsSet(),
            'session_driver' => config('session.driver'),
            'db_connection' => $connection,
            'db_host_set' => (bool) config("database.connections.{$connection}.host"),
            'db_name_set' => (bool) config("database.connections.{$connection}.database"),
            'db_reachable' => $reachable,
            'tables_ready' => $tables,
            'installed' => $installed,
            'products' => $products,
        ]);
    }

    public function installForm(): Response
    {
        abort_unless($this->installerOpen(), 404);

        return response()->view('system.install');
    }

    public function install(Request $request): Response
    {
        abort_unless($this->installerOpen(), 404);

        $given = (string) $request->input('clave', '');
        if (! hash_equals((string) config('gdm.admin_password'), $given)) {
            return response()->view('system.install', ['error' => 'La contraseña no coincide con ADMIN_PASSWORD.'], 403);
        }

        try {
            $code = Artisan::call('gdm:setup');
        } catch (Throwable $e) {
            return response()->view('system.install', ['error' => 'No se pudo instalar: '.$e->getMessage()], 500);
        }

        if ($code !== 0) {
            return response()->view('system.install', ['error' => 'No se pudo instalar: '.trim(Artisan::output())], 500);
        }

        return response()->view('system.installed');
    }

    private function secretsSet(): bool
    {
        return config('gdm.admin_email') && config('gdm.admin_password');
    }

    private function installerOpen(): bool
    {
        if (! $this->secretsSet()) {
            return false;
        }

        try {
            return ! (Schema::hasTable('users') && User::exists());
        } catch (Throwable) {
            return true;
        }
    }
}
