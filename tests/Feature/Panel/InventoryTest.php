<?php

namespace Tests\Feature\Panel;

use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    public function test_admin_records_entry(): void
    {
        $user = $this->admin();
        $p = $this->product(['stock' => 5, 'avg_cost_cents' => 420]);
        $this->actingAs($user)->post("/panel/inventario/{$p->id}/entradas", ['quantity' => 5, 'unit_cost' => '4.20', 'note' => 'Compra'])->assertRedirect();
        $this->assertSame(10, $p->refresh()->stock);
        $this->assertSame($user->id, StockMovement::first()->user_id);
    }

    public function test_adjust_without_reason_fails(): void
    {
        $p = $this->product();
        $this->actingAs($this->admin())->post("/panel/inventario/{$p->id}/ajustes", ['delta' => -1, 'reason' => ''])->assertSessionHasErrors('reason');
    }

    public function test_adjust_below_zero_shows_error(): void
    {
        $p = $this->product(['stock' => 1]);
        $this->actingAs($this->admin())->post("/panel/inventario/{$p->id}/ajustes", ['delta' => -5, 'reason' => 'Conteo'])->assertSessionHasErrors();
        $this->assertSame(1, $p->refresh()->stock);
    }

    public function test_low_stock_filter(): void
    {
        $this->product(['code' => 'BAJO', 'stock' => 2]);
        $this->product(['code' => 'ALTO', 'stock' => 10]);
        $this->actingAs($this->admin())->get('/panel/inventario?bajo=1')->assertSee('BAJO')->assertDontSee('ALTO');
    }

    public function test_history_shows_movements_newest_first(): void
    {
        $p = $this->product();
        $this->actingAs($this->admin());
        $this->post("/panel/inventario/{$p->id}/entradas", ['quantity' => 1, 'unit_cost' => '1', 'note' => 'PRIMERA']);
        $this->post("/panel/inventario/{$p->id}/entradas", ['quantity' => 1, 'unit_cost' => '1', 'note' => 'SEGUNDA']);
        $this->get("/panel/inventario/{$p->id}")->assertSeeInOrder(['SEGUNDA', 'PRIMERA']);
    }

    public function test_seller_sees_stock_but_not_cost_and_cannot_post(): void
    {
        $p = $this->product(['stock' => 77, 'avg_cost_cents' => 987654]);
        $this->actingAs($this->seller());
        $this->get('/panel/inventario')->assertOk()->assertSee('77')->assertDontSee('9.876,54');
        $this->get("/panel/inventario/{$p->id}")->assertOk()->assertDontSee('9.876,54');
        $this->post("/panel/inventario/{$p->id}/entradas", ['quantity' => 1, 'unit_cost' => '1'])->assertForbidden();
        $this->post("/panel/inventario/{$p->id}/ajustes", ['delta' => 1, 'reason' => 'x'])->assertForbidden();
    }
}
