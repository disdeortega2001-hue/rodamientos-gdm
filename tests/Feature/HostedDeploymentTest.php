<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\CreatesCatalog;
use Tests\TestCase;

class HostedDeploymentTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    private function workbook(): UploadedFile
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        foreach (['B' => 'UC207 SKF', 'F' => 6, 'G' => 5, 'H' => 14, 'I' => 25] as $col => $value) {
            $sheet->setCellValue("{$col}7", $value);
        }
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($book))->save($path);

        return new UploadedFile($path, 'cotizacion.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_mysql_connection_accepts_wasmer_db_name(): void
    {
        $source = file_get_contents(config_path('database.php'));
        $this->assertStringContainsString("env('DB_DATABASE', env('DB_NAME'", $source);
    }

    public function test_admin_imports_excel_from_panel(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->actingAs($this->admin())->post('/panel/importar', ['archivo' => $this->workbook()])
            ->assertRedirect()->assertSessionHasNoErrors();
        $product = Product::where('code', 'UC207 SKF')->first();
        $this->assertSame(5, $product->stock);
        $this->assertSame(2500, $product->price_cents);
    }

    public function test_second_import_from_panel_is_refused(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->actingAs($this->admin());
        $this->post('/panel/importar', ['archivo' => $this->workbook()]);
        $this->post('/panel/importar', ['archivo' => $this->workbook()])->assertSessionHasErrors('archivo');
    }

    public function test_import_rejects_other_file_types(): void
    {
        $this->actingAs($this->admin())->post('/panel/importar', ['archivo' => UploadedFile::fake()->create('x.php', 1, 'application/x-php')])
            ->assertSessionHasErrors('archivo');
    }

    public function test_seller_cannot_import_or_download_backup(): void
    {
        $this->actingAs($this->seller());
        $this->get('/panel/importar')->assertForbidden();
        $this->post('/panel/importar', [])->assertForbidden();
        $this->get('/panel/respaldo')->assertForbidden();
    }

    public function test_admin_downloads_full_backup(): void
    {
        $this->product(['code' => 'RESPALDO1', 'stock' => 7, 'avg_cost_cents' => 420, 'price_cents' => 700]);
        $response = $this->actingAs($this->admin())->get('/panel/respaldo')->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'bak').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $book = IOFactory::load($path);
        $this->assertSame(['Productos', 'Clientes', 'Pedidos', 'Piezas', 'Pagos', 'Movimientos', 'Ajustes'], $book->getSheetNames());
        $rows = collect($book->getSheetByName('Productos')->toArray())->flatten();
        $this->assertTrue($rows->contains('RESPALDO1'));
        $this->assertTrue($rows->contains(fn ($v) => (float) $v === 4.2));
        unlink($path);
    }

    public function test_settings_page_links_tools(): void
    {
        $this->actingAs($this->admin())->get('/panel/ajustes')->assertSee('/panel/respaldo', false)->assertSee('/panel/importar', false);
    }

    public function test_uploaded_photo_is_served_through_media_route(): void
    {
        Storage::fake('public');
        $p = $this->product(['code' => 'MEDIA1']);
        $this->actingAs($this->admin())->post("/panel/productos/{$p->id}/imagenes", ['image' => UploadedFile::fake()->image('a.png')]);
        $image = $p->images()->first();
        $this->assertStringContainsString('/media/', $image->url());
        $this->get('/media/'.$image->path)->assertOk();
    }

    public function test_media_route_does_not_leave_public_disk(): void
    {
        Storage::fake('public');
        $this->get('/media/../../.env')->assertNotFound();
        $this->get('/media/no-existe.png')->assertNotFound();
    }

    public function test_setup_migrates_seeds_and_creates_admin_once(): void
    {
        config(['gdm.admin_email' => 'dueno@example.com', 'gdm.admin_password' => 'clave-segura-123']);
        $this->artisan('gdm:setup')->assertSuccessful();
        $this->assertSame(18, Product::count());
        $this->assertSame(Role::Admin, User::where('email', 'dueno@example.com')->first()->role);

        Setting::put('business_name', 'Nombre editado');
        Product::where('code', 'UC207 SKF')->update(['price_cents' => 9999]);
        $this->artisan('gdm:setup')->assertSuccessful();
        $this->assertSame(1, User::count());
        $this->assertSame('Nombre editado', Setting::get('business_name'));
        $this->assertSame(9999, Product::where('code', 'UC207 SKF')->first()->price_cents);
    }

    public function test_setup_refuses_short_admin_password_without_creating_user(): void
    {
        config(['gdm.admin_email' => 'dueno@example.com', 'gdm.admin_password' => 'corta']);
        $this->artisan('gdm:setup')->assertFailed();
        $this->assertSame(0, User::count());
    }

    public function test_setup_without_admin_settings_still_prepares_catalog(): void
    {
        config(['gdm.admin_email' => null, 'gdm.admin_password' => null]);
        $this->artisan('gdm:setup')->assertSuccessful();
        $this->assertSame(0, User::count());
        $this->assertSame(18, Product::count());
    }

    public function test_deployment_files_carry_no_secrets_or_costs(): void
    {
        $yaml = file_get_contents(base_path('app.yaml'));
        $this->assertStringContainsString('engine: mysql', $yaml);
        $this->assertStringNotContainsString('APP_KEY', $yaml);
        $this->assertStringNotContainsString('ADMIN_PASSWORD', $yaml);
    }
}
