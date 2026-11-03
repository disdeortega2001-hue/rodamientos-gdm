<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;

class ExcelImporter
{
    public function alreadyImported(): bool
    {
        return StockMovement::where('type', MovementType::Opening)->exists() || Order::where('source', 'import')->exists();
    }

    /**
     * @return array{products:int, stock:int}
     *
     * @throws RuntimeException
     */
    public function import(string $path): array
    {
        if ($this->alreadyImported()) {
            throw new RuntimeException('La carga inicial ya se hizo. No se volvió a aplicar.');
        }

        $codes = collect((require database_path('data/catalog.php'))['products'])->pluck('code', 'excel');
        $book = IOFactory::load($path);
        $sheet = $book->getSheet(0);
        $date = $this->historyDate($book);

        return DB::transaction(function () use ($sheet, $codes, $date) {
            $sold = [];
            $count = 0;
            $stock = 0;

            for ($row = 7; ($excelCode = trim((string) $sheet->getCell("B{$row}")->getCalculatedValue())) !== ''; $row++) {
                $code = $codes[$excelCode] ?? throw new RuntimeException("El código {$excelCode} no está en el catálogo.");
                $product = Product::where('code', $code)->first() ?? throw new RuntimeException("Falta el producto {$code} en el catálogo.");

                $units = (int) $sheet->getCell("G{$row}")->getCalculatedValue();
                $product->update([
                    'stock' => $units,
                    'avg_cost_cents' => Money::parseCents($sheet->getCell("H{$row}")->getCalculatedValue()),
                    'price_cents' => Money::parseCents($sheet->getCell("I{$row}")->getCalculatedValue()),
                ]);
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => MovementType::Opening,
                    'quantity' => $units,
                    'unit_cost_cents' => $product->avg_cost_cents,
                    'note' => 'Carga inicial desde Excel',
                ]);

                $exits = (int) $sheet->getCell("F{$row}")->getCalculatedValue();
                if ($exits > 0) {
                    $sold[] = [$product, $exits];
                }
                $count++;
                $stock += $units;
            }

            if ($count === 0) {
                throw new RuntimeException('El archivo no tiene productos desde la fila 7.');
            }

            $this->recordHistory($sold, $date);

            return ['products' => $count, 'stock' => $stock];
        });
    }

    /** @param array<int, array{0: Product, 1: int}> $sold */
    private function recordHistory(array $sold, Carbon $date): void
    {
        if ($sold === []) {
            return;
        }

        $order = Order::create([
            'number' => 'GDM-HIST',
            'status' => OrderStatus::Delivered,
            'source' => 'import',
            'note' => 'Ventas previas registradas en el Excel',
            'confirmed_at' => $date,
            'delivered_at' => $date,
        ]);

        $total = 0;
        foreach ($sold as [$product, $quantity]) {
            $order->items()->create([
                'product_id' => $product->id,
                'code' => $product->code,
                'measures' => $product->measures,
                'quantity' => $quantity,
                'unit_price_cents' => $product->price_cents,
                'unit_cost_cents' => $product->avg_cost_cents,
            ]);
            $total += $quantity * $product->price_cents;
        }

        $order->payments()->create([
            'method' => PaymentMethod::Other,
            'reference' => 'Excel',
            'amount_cents' => $total,
            'paid_at' => $date,
        ]);
    }

    private function historyDate(Spreadsheet $book): Carbon
    {
        if ($book->getSheetCount() >= 3) {
            $serial = $book->getSheet(2)->getCell('F5')->getCalculatedValue();
            if (is_numeric($serial)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($serial))->setTime(12, 0);
            }
        }

        return now();
    }
}
