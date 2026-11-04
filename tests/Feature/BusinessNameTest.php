<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class BusinessNameTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    private const OLD = 'Rodamientos'.' GDM';

    public function test_public_pages_use_the_correct_name(): void
    {
        foreach (['/', '/empresa', '/guia', '/catalogo', '/carrito', '/creditos'] as $url) {
            $this->get($url)->assertOk()->assertSee('Rodamiento GDM')->assertDontSee(self::OLD);
        }
    }

    public function test_logo_uses_the_correct_name(): void
    {
        $this->get('/')->assertSee('Rodamiento <span>GDM</span>', false)->assertDontSee('Rodamientos'.' <span>GDM', false);
    }

    public function test_previously_saved_name_is_corrected(): void
    {
        Setting::put('business_name', self::OLD);
        $this->assertSame('Rodamiento GDM', Setting::get('business_name'));
    }

    public function test_admin_can_still_choose_another_name(): void
    {
        Setting::put('business_name', 'Otro Nombre');
        $this->assertSame('Otro Nombre', Setting::get('business_name'));
    }

    public function test_delivery_note_and_order_message_use_the_correct_name(): void
    {
        Setting::put('business_name', self::OLD);
        $p = $this->product(['price_cents' => 600]);
        $order = app(OrderService::class)->create([['product_id' => $p->id, 'quantity' => 1]], $this->customer(), 'counter', null);
        app(OrderService::class)->confirm($order, $this->seller());
        $this->get('/nota/'.$order->refresh()->delivery_note_token)->assertSee('Rodamiento GDM')->assertDontSee(self::OLD);

        $message = rawurldecode($this->postJson('/pedido', [
            'name' => 'Ana', 'phone' => '04121234567', 'token' => (string) \Illuminate\Support\Str::uuid(),
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
        ])->json('whatsapp_url'));
        $this->assertStringContainsString('Hola, Rodamiento GDM.', $message);
    }

    public function test_source_has_no_old_name_left(): void
    {
        foreach (['resources/views', 'app', 'config', 'database/seeders'] as $dir) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($dir), \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if ($file->getFilename() === 'Setting.php') {
                    continue;
                }
                $this->assertStringNotContainsString(self::OLD, file_get_contents($file->getPathname()), $file->getPathname());
            }
        }
    }
}
