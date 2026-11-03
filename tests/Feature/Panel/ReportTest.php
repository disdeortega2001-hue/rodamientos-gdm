<?php

namespace Tests\Feature\Panel;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\CreatesCatalog;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    private function sale(string $code, int $qty, int $price, int $cost, string $status = 'confirmed', string $date = '2026-09-18'): Order
    {
        $p = $this->product(['code' => $code, 'price_cents' => $price, 'avg_cost_cents' => $cost, 'stock' => 100]);
        $order = app(OrderService::class)->create([['product_id' => $p->id, 'quantity' => $qty]], \App\Models\Customer::firstOrCreate(['phone' => '04121234567'], ['name' => 'Ana']), 'web', null);
        $order->update(['status' => $status, 'confirmed_at' => $status === 'new' ? null : $date.' 10:00:00']);

        return $order;
    }

    private function fixture(): void
    {
        $this->sale('UC207', 6, 2500, 1400);
        $this->sale('UC206', 10, 2000, 1200);
        $this->sale('UC205', 10, 2000, 1000);
    }

    private function summary(string $from = '2026-09-01', string $to = '2026-09-30'): array
    {
        return app(ReportService::class)->summary(Carbon::parse($from), Carbon::parse($to));
    }

    public function test_summary_values(): void
    {
        $this->fixture();
        $this->assertSame(['sales_cents' => 55000, 'cost_cents' => 30400, 'profit_cents' => 24600], $this->summary());
    }

    public function test_summary_excludes_new_and_cancelled(): void
    {
        $this->sale('A', 1, 1000, 500, 'new');
        $this->sale('B', 1, 1000, 500, 'cancelled');
        $this->assertSame(0, $this->summary()['sales_cents']);
    }

    public function test_summary_includes_delivered(): void
    {
        $this->sale('A', 1, 1000, 500, 'delivered');
        $this->assertSame(1000, $this->summary()['sales_cents']);
    }

    public function test_summary_respects_dates(): void
    {
        $this->sale('A', 1, 1000, 500, 'confirmed', '2026-09-30');
        $this->sale('B', 1, 2000, 500, 'confirmed', '2026-10-01');
        $this->assertSame(1000, $this->summary()['sales_cents']);
    }

    public function test_by_product_groups_lines(): void
    {
        $this->sale('A', 2, 1000, 500);
        $rows = app(ReportService::class)->byProduct(Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));
        $this->assertCount(1, $rows);
        $this->assertSame('A', $rows[0]->code);
        $this->assertSame(2, (int) $rows[0]->quantity);
        $this->assertSame(2000, (int) $rows[0]->sales_cents);
        $this->assertSame(1000, (int) $rows[0]->profit_cents);
    }

    public function test_report_page_shows_totals(): void
    {
        $this->fixture();
        $this->actingAs($this->admin())->get('/panel/reportes?from=2026-09-01&to=2026-09-30')->assertOk()->assertSee('$550')->assertSee('$246');
    }

    public function test_invalid_range_rejected(): void
    {
        $this->actingAs($this->admin())->get('/panel/reportes?from=2026-10-01&to=2026-09-01')->assertSessionHasErrors('to');
    }

    public function test_export_is_xlsx_with_totals(): void
    {
        $this->fixture();
        $response = $this->actingAs($this->admin())->get('/panel/reportes/exportar?from=2026-09-01&to=2026-09-30')->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'rep').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $values = collect(IOFactory::load($path)->getActiveSheet()->toArray())->flatten();
        $this->assertTrue($values->contains(fn ($v) => (float) $v === 550.0));
        $this->assertTrue($values->contains(fn ($v) => (float) $v === 246.0));
        unlink($path);
    }

    public function test_seller_denied_reports(): void
    {
        $this->actingAs($this->seller());
        $this->get('/panel/reportes')->assertForbidden();
        $this->get('/panel/reportes/exportar')->assertForbidden();
    }

    public function test_dashboard_admin_sees_profit_seller_does_not(): void
    {
        $this->actingAs($this->admin())->get('/panel')->assertSee('Ganancia del mes');
        $this->actingAs($this->seller())->get('/panel')->assertOk()->assertDontSee('Ganancia');
    }

    public function test_dashboard_lists_low_stock_and_new_orders(): void
    {
        $this->product(['code' => 'BAJITO', 'stock' => 1]);
        $order = $this->sale('A', 1, 1000, 500, 'new');
        $this->actingAs($this->seller())->get('/panel')->assertSee('BAJITO')->assertSee($order->number);
    }
}
