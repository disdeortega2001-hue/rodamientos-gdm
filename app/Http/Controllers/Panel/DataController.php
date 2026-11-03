<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Services\ExcelImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataController extends Controller
{
    public function importForm(ExcelImporter $importer): View
    {
        return view('panel.data.import', ['imported' => $importer->alreadyImported()]);
    }

    public function import(Request $request, ExcelImporter $importer): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ], ['archivo.mimes' => 'El archivo debe ser un Excel .xlsx.', 'archivo.required' => 'Elige el archivo de Excel.']);

        try {
            $totals = $importer->import($request->file('archivo')->getRealPath());
        } catch (RuntimeException $e) {
            return back()->withErrors(['archivo' => $e->getMessage()]);
        }

        return redirect('/panel/inventario')->with('status', "Carga completa: {$totals['products']} productos y {$totals['stock']} unidades.");
    }

    public function backup(): StreamedResponse
    {
        $book = new Spreadsheet;
        $book->removeSheetByIndex(0);

        $this->sheet($book, 'Productos', ['Código', 'Categoría', 'Medidas', 'Descripción', 'Marca', 'Precio ($)', 'Costo ($)', 'Existencia', 'Visible'],
            Product::with('category')->orderBy('code')->get()->map(fn (Product $p) => [$p->code, $p->category->name, $p->measures, $p->description, $p->brand, $this->usd($p->price_cents), $this->usd($p->avg_cost_cents), $p->stock, $p->is_visible ? 'Sí' : 'No']));
        $this->sheet($book, 'Clientes', ['Nombre', 'Teléfono', 'Notas'],
            Customer::orderBy('name')->get()->map(fn (Customer $c) => [$c->name, $c->phone, $c->notes]));
        $this->sheet($book, 'Pedidos', ['Número', 'Fecha', 'Cliente', 'Teléfono', 'Estado', 'Origen', 'Tasa', 'Nota', 'Nota de entrega'],
            Order::with('customer')->orderBy('id')->get()->map(fn (Order $o) => [$o->number, $o->created_at?->format('Y-m-d H:i'), $o->customer?->name, $o->customer?->phone, $o->status->label(), $o->source, $o->usd_rate, $o->note, $o->delivery_note_number]));
        $this->sheet($book, 'Piezas', ['Pedido', 'Código', 'Medidas', 'Cantidad', 'Precio ($)', 'Costo ($)'],
            OrderItem::with('order')->orderBy('order_id')->get()->map(fn (OrderItem $i) => [$i->order->number, $i->code, $i->measures, $i->quantity, $this->usd($i->unit_price_cents), $this->usd($i->unit_cost_cents)]));
        $this->sheet($book, 'Pagos', ['Pedido', 'Fecha', 'Forma', 'Referencia', 'Monto ($)'],
            Payment::with('order')->orderBy('id')->get()->map(fn (Payment $p) => [$p->order->number, $p->paid_at->format('Y-m-d'), $p->method->label(), $p->reference, $this->usd($p->amount_cents)]));
        $this->sheet($book, 'Movimientos', ['Fecha', 'Código', 'Tipo', 'Cantidad', 'Costo ($)', 'Nota'],
            StockMovement::with('product')->orderBy('id')->get()->map(fn (StockMovement $m) => [$m->created_at?->format('Y-m-d H:i'), $m->product->code, $m->type->label(), $m->quantity, $this->usd($m->unit_cost_cents), $m->note]));
        $this->sheet($book, 'Ajustes', ['Clave', 'Valor'],
            Setting::orderBy('key')->get()->map(fn (Setting $s) => [$s->key, $s->value]));

        return response()->streamDownload(fn () => (new Xlsx($book))->save('php://output'), 'respaldo-gdm-'.now()->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function sheet(Spreadsheet $book, string $title, array $headers, iterable $rows): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle($title);
        $sheet->fromArray($headers);
        $line = 2;
        foreach ($rows as $row) {
            $sheet->fromArray($row, null, "A{$line}", true);
            $line++;
        }
    }

    private function usd(?int $cents): ?float
    {
        return $cents === null ? null : $cents / 100;
    }
}
