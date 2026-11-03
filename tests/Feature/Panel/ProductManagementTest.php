<?php

namespace Tests\Feature\Panel;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\CreatesCatalog;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    private function form(array $extra = []): array
    {
        return $extra + [
            'category_id' => $this->category()->id,
            'code' => 'NUEVO 1',
            'measures' => '10 x 20 mm',
            'description' => 'Rodamiento nuevo',
            'price' => '12.50',
            'low_stock_threshold' => 3,
            'track_stock' => 1,
            'is_visible' => 1,
        ];
    }

    public function test_admin_creates_product(): void
    {
        $this->actingAs($this->admin())->post('/panel/productos', $this->form())->assertRedirect();
        $this->assertSame(1250, Product::first()->price_cents);
        $this->get('/catalogo')->assertSee('NUEVO 1')->assertSee('$12,50');
    }

    public function test_admin_changes_price(): void
    {
        $p = $this->product(['code' => 'A1', 'price_cents' => 600]);
        $this->actingAs($this->admin())->put("/panel/productos/{$p->id}", $this->form(['code' => 'A1', 'price' => '9']))->assertRedirect();
        $this->assertSame(900, $p->refresh()->price_cents);
    }

    public function test_edit_does_not_touch_stock_or_cost(): void
    {
        $p = $this->product(['code' => 'A1', 'stock' => 7, 'avg_cost_cents' => 360]);
        $this->actingAs($this->admin())->put("/panel/productos/{$p->id}", $this->form(['code' => 'A1', 'stock' => 99, 'avg_cost_cents' => 1]));
        $p->refresh();
        $this->assertSame(7, $p->stock);
        $this->assertSame(360, $p->avg_cost_cents);
    }

    public function test_seller_can_list_but_not_edit(): void
    {
        $p = $this->product();
        $this->actingAs($this->seller());
        $this->get('/panel/productos')->assertOk()->assertSee($p->code);
        $this->get("/panel/productos/{$p->id}/editar")->assertForbidden();
        $this->post('/panel/productos', $this->form())->assertForbidden();
        $this->put("/panel/productos/{$p->id}", $this->form())->assertForbidden();
    }

    public function test_seller_does_not_see_cost(): void
    {
        $this->product(['avg_cost_cents' => 987654]);
        $this->actingAs($this->seller())->get('/panel/productos')->assertDontSee('9.876,54');
    }

    public function test_admin_sees_cost(): void
    {
        $this->product(['avg_cost_cents' => 987654]);
        $this->actingAs($this->admin())->get('/panel/productos')->assertSee('$9.876,54');
    }

    public function test_hide_product(): void
    {
        $p = $this->product(['code' => 'A1']);
        $this->actingAs($this->admin())->put("/panel/productos/{$p->id}", array_diff_key($this->form(['code' => 'A1']), ['is_visible' => 1]));
        $this->assertFalse($p->refresh()->is_visible);
        $this->get('/catalogo/a1')->assertNotFound();
    }

    public function test_price_can_be_empty_and_never_negative(): void
    {
        $this->actingAs($this->admin());
        $this->post('/panel/productos', $this->form(['price' => '']));
        $this->assertNull(Product::first()->price_cents);
        $this->post('/panel/productos', $this->form(['code' => 'B', 'price' => '-1']))->assertSessionHasErrors('price');
    }

    public function test_code_must_be_unique(): void
    {
        $this->product(['code' => 'NUEVO 1']);
        $this->actingAs($this->admin())->post('/panel/productos', $this->form())->assertSessionHasErrors('code');
        $this->post('/panel/productos', $this->form(['code' => 'nuevo-1']))->assertSessionHasErrors('code');
    }

    public function test_upload_accepts_webp_and_rejects_php(): void
    {
        Storage::fake('public');
        $p = $this->product();
        $this->actingAs($this->admin());
        $this->post("/panel/productos/{$p->id}/imagenes", ['image' => UploadedFile::fake()->image('a.webp')])->assertSessionHasNoErrors();
        $this->assertCount(1, $p->images);
        Storage::disk('public')->assertExists($p->images->first()->path);

        $this->post("/panel/productos/{$p->id}/imagenes", ['image' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php')])->assertSessionHasErrors('image');
        $this->post("/panel/productos/{$p->id}/imagenes", ['image' => UploadedFile::fake()->image('big.png')->size(5000)])->assertSessionHasErrors('image');
    }

    public function test_image_stores_credit_and_reference_flag(): void
    {
        Storage::fake('public');
        $p = $this->product();
        $this->actingAs($this->admin())->post("/panel/productos/{$p->id}/imagenes", [
            'image' => UploadedFile::fake()->image('a.png'),
            'is_reference' => 1,
            'credit_author' => 'Autor',
            'credit_license' => 'CC BY 4.0',
            'credit_url' => 'https://example.com/foto',
        ]);
        $image = $p->images()->first();
        $this->assertTrue($image->is_reference);
        $this->assertSame('Autor', $image->credit_author);
    }

    public function test_delete_image_removes_file(): void
    {
        Storage::fake('public');
        $p = $this->product();
        $this->actingAs($this->admin())->post("/panel/productos/{$p->id}/imagenes", ['image' => UploadedFile::fake()->image('a.png')]);
        $image = $p->images()->first();
        $this->delete("/panel/imagenes/{$image->id}")->assertRedirect();
        Storage::disk('public')->assertMissing($image->path);
        $this->assertCount(0, $p->images()->get());
    }

    public function test_product_with_orders_is_hidden_instead_of_deleted(): void
    {
        $p = $this->product();
        app(OrderService::class)->create([['product_id' => $p->id, 'quantity' => 1]], Customer::create(['name' => 'A', 'phone' => '04121111111']), 'web', null);
        $this->actingAs($this->admin())->delete("/panel/productos/{$p->id}")->assertRedirect();
        $this->assertFalse($p->refresh()->is_visible);
    }

    public function test_product_without_history_is_deleted(): void
    {
        $p = $this->product();
        $this->actingAs($this->admin())->delete("/panel/productos/{$p->id}");
        $this->assertNull(Product::find($p->id));
    }

    public function test_category_crud_and_protection(): void
    {
        $this->actingAs($this->admin());
        $this->post('/panel/categorias', ['name' => 'Nueva', 'position' => 5])->assertRedirect();
        $category = Category::where('name', 'Nueva')->firstOrFail();
        $this->put("/panel/categorias/{$category->id}", ['name' => 'Renombrada', 'position' => 1]);
        $this->assertSame('renombrada', $category->refresh()->slug);

        $this->product(['category_id' => $category->id]);
        $this->delete("/panel/categorias/{$category->id}")->assertSessionHasErrors();
        $this->assertNotNull(Category::find($category->id));
    }

    public function test_seller_cannot_manage_categories(): void
    {
        $this->actingAs($this->seller())->get('/panel/categorias')->assertForbidden();
    }
}
