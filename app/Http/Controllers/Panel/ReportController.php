<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        [$from, $to] = $this->range($request);

        return view('panel.reports.index', [
            'from' => $from,
            'to' => $to,
            'summary' => $reports->summary($from, $to),
            'rows' => $reports->byProduct($from, $to),
        ]);
    }

    public function export(Request $request, ReportService $reports): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $summary = $reports->summary($from, $to);

        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Ventas');
        $sheet->fromArray(['Código', 'Cantidad', 'Ventas ($)', 'Costo ($)', 'Ganancia ($)']);
        $row = 2;
        foreach ($reports->byProduct($from, $to) as $line) {
            $sheet->fromArray([$line->code, (int) $line->quantity, $line->sales_cents / 100, $line->cost_cents / 100, $line->profit_cents / 100], null, "A{$row}");
            $row++;
        }
        $sheet->fromArray(['Total', null, $summary['sales_cents'] / 100, $summary['cost_cents'] / 100, $summary['profit_cents'] / 100], null, "A{$row}");

        $name = 'ventas-'.$from->format('Y-m-d').'-a-'.$to->format('Y-m-d').'.xlsx';

        return response()->streamDownload(fn () => (new Xlsx($book))->save('php://output'), $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function range(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ], ['to.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.']);

        return [
            isset($data['from']) ? Carbon::parse($data['from']) : now()->startOfMonth(),
            isset($data['to']) ? Carbon::parse($data['to']) : now(),
        ];
    }
}
