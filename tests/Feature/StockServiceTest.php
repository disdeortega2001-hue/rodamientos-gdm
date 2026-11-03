<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Exceptions\InsufficientStock;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\CreatesCatalog;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    private function stock(): StockService
    {
        return app(StockService::class);
    }

    public function test_entry_updates_weighted_cost(): void
    {
        $p = $this->product(['stock' => 10, 'avg_cost_cents' => 420]);
        $m = $this->stock()->entry($p, 10, 500, $this->admin(), 'Compra');
        $p->refresh();
        $this->assertSame(20, $p->stock);
        $this->assertSame(460, $p->avg_cost_cents);
        $this->assertSame(MovementType::Entry, $m->type);
        $this->assertSame(10, $m->quantity);
        $this->assertSame(500, $m->unit_cost_cents);
    }

    public function test_entry_rounds_half_up(): void
    {
        $p = $this->product(['stock' => 1, 'avg_cost_cents' => 100]);
        $this->stock()->entry($p, 1, 101, $this->admin(), null);
        $this->assertSame(101, $p->refresh()->avg_cost_cents);
    }

    public function test_entry_with_zero_stock_uses_new_cost(): void
    {
        $p = $this->product(['stock' => 0, 'avg_cost_cents' => 999]);
        $this->stock()->entry($p, 3, 200, $this->admin(), null);
        $this->assertSame(200, $p->refresh()->avg_cost_cents);
    }

    public function test_entry_rejects_non_positive_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->stock()->entry($this->product(), 0, 200, $this->admin(), null);
    }

    public function test_adjust_rejects_negative_result(): void
    {
        $p = $this->product(['stock' => 2]);
        try {
            $this->stock()->adjust($p, -3, 'Conteo', $this->admin());
            $this->fail('Se esperaba InsufficientStock');
        } catch (InsufficientStock) {
            $this->assertSame(2, $p->refresh()->stock);
        }
    }

    public function test_adjust_requires_reason(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->stock()->adjust($this->product(), 1, ' ', $this->admin());
    }

    public function test_adjust_records_movement_with_user(): void
    {
        $user = $this->admin();
        $p = $this->product(['stock' => 5]);
        $m = $this->stock()->adjust($p, -2, 'Pieza dañada', $user);
        $this->assertSame(3, $p->refresh()->stock);
        $this->assertSame(MovementType::Adjustment, $m->type);
        $this->assertSame($user->id, $m->user_id);
        $this->assertSame('Pieza dañada', $m->note);
    }
}
