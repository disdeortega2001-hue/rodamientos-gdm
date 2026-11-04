<?php

namespace Tests\Feature;

use App\Enums\Availability;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Services\ReportService;
use Carbon\Carbon;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportExcelTest extends TestCase
{
    use RefreshDatabase;

    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    protected function tearDown(): void
    {
        array_map('unlink', $this->files);
        parent::tearDown();
    }

    /** @param array<int, array{0:string,1:int,2:int,3:float,4:float}> $rows code, exits, stock, cost, price */
    private function workbook(array $rows): string
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A6', 'N°');
        foreach ($rows as $i => [$code, $exits, $stock, $cost, $price]) {
            $r = 7 + $i;
            $sheet->setCellValue("A{$r}", $i + 1);
            $sheet->setCellValue("B{$r}", $code);
            $sheet->setCellValue("F{$r}", $exits);
            $sheet->setCellValue("G{$r}", $stock);
            $sheet->setCellValue("H{$r}", $cost);
            $sheet->setCellValue("I{$r}", $price);
        }
        $book->createSheet();
        $book->createSheet()->setCellValue('F5', 46283);

        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($book))->save($path);
        $this->files[] = $path;

        return $path;
    }

    public function test_import_sets_price_cost_stock(): void
    {
        $this->artisan('gdm:import-excel', ['path' => $this->workbook([['UC207 SKF', 0, 5, 14, 25]])])->assertSuccessful();
        $p = Product::where('code', 'UC207 SKF')->firstOrFail();
        $this->assertSame(2500, $p->price_cents);
        $this->assertSame(1400, $p->avg_cost_cents);
        $this->assertSame(5, $p->stock);
    }

    public function test_import_creates_opening_movement(): void
    {
        $this->artisan('gdm:import-excel', ['path' => $this->workbook([['UC207 SKF', 0, 5, 14, 25]])]);
        $m = Product::where('code', 'UC207 SKF')->first()->movements()->first();
        $this->assertSame(MovementType::Opening, $m->type);
        $this->assertSame(5, $m->quantity);
    }

    public function test_import_records_history_without_reducing_stock(): void
    {
        $this->artisan('gdm:import-excel', ['path' => $this->workbook([['UC207 SKF', 6, 5, 14, 25]])]);
        $order = Order::with('items')->firstOrFail();
        $this->assertSame(6, $order->items->first()->quantity);
        $this->assertSame(5, Product::where('code', 'UC207 SKF')->first()->stock);
    }

    public function test_history_order_is_delivered_and_paid(): void
    {
        $this->artisan('gdm:import-excel', ['path' => $this->workbook([['UC207 SKF', 6, 5, 14, 25]])]);
        $order = Order::firstOrFail();
        $this->assertSame('import', $order->source);
        $this->assertSame('GDM-HIST', $order->number);
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertSame(PaymentStatus::Paid, $order->paymentStatus());
        $this->assertSame('2026-09-18', $order->confirmed_at->format('Y-m-d'));
    }

    public function test_totals_match_fixture(): void
    {
        $this->artisan('gdm:import-excel', ['path' => $this->workbook([
            ['UC207 SKF', 6, 5, 14, 25], ['UC206 SKF', 10, 5, 12, 20], ['UC205 SKF', 10, 5, 10, 20],
        ])]);
        $this->assertSame(
            ['sales_cents' => 55000, 'cost_cents' => 30400, 'profit_cents' => 24600],
            app(ReportService::class)->summary(Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'))
        );
    }

    public function test_excel_code_maps_to_catalog_code(): void
    {
        $this->artisan('gdm:import-excel', ['path' => $this->workbook([['60X90X10', 0, 10, 3, 8], ['6306 2RS', 0, 6, 7, 15]])])->assertSuccessful();
        $this->assertSame(800, Product::where('code', 'ESTOPERA 60X90X10')->first()->price_cents);
        $this->assertSame(1500, Product::where('code', '6306 2RS SKF')->first()->price_cents);
    }

    public function test_unknown_code_aborts_without_changes(): void
    {
        $this->artisan('gdm:import-excel', ['path' => $this->workbook([['UC207 SKF', 0, 5, 14, 25], ['NO EXISTE', 0, 1, 1, 1]])])->assertFailed();
        $this->assertSame(0, Product::where('code', 'UC207 SKF')->first()->stock);
    }

    public function test_second_import_is_refused(): void
    {
        $path = $this->workbook([['UC207 SKF', 6, 5, 14, 25]]);
        $this->artisan('gdm:import-excel', ['path' => $path])->assertSuccessful();
        $this->artisan('gdm:import-excel', ['path' => $path])->assertFailed();
        $this->assertSame(1, Order::count());
    }

    public function test_missing_file_fails(): void
    {
        $this->artisan('gdm:import-excel', ['path' => 'no-existe.xlsx'])->assertFailed();
    }

    public function test_catalog_has_all_products_and_kit(): void
    {
        $this->assertSame(18, Product::count());
        $kit = Product::where('code', 'Kit punta de eje de rotativa')->firstOrFail();
        $this->assertTrue($kit->track_stock);
        $this->assertSame(4500, $kit->price_cents);
        $this->assertSame(0, $kit->stock);
        $this->assertSame(Availability::Out, $kit->availability());
        $this->assertStringContainsString('SKF', $kit->description);
        $this->assertStringContainsString('rueda de rotativa', $kit->description);
    }

    public function test_seeder_is_repeatable(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->assertSame(18, Product::count());
    }
}
