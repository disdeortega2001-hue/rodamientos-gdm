<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProductionConfigTest extends TestCase
{
    public function test_production_forces_https_links(): void
    {
        $this->app['env'] = 'production';
        (new AppServiceProvider($this->app))->boot();
        $this->assertStringStartsWith('https://', url('/catalogo'));
        URL::forceScheme(null);
    }

    public function test_production_env_example_is_safe(): void
    {
        $env = file_get_contents(base_path('.env.production.example'));
        foreach (['APP_ENV=production', 'APP_DEBUG=false', 'DB_CONNECTION=mysql', 'SESSION_SECURE_COOKIE=true', 'APP_LOCALE=es'] as $line) {
            $this->assertStringContainsString($line, $env);
        }
    }
}
