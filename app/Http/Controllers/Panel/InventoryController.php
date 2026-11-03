<?php

namespace App\Http\Controllers\Panel;

use App\Exceptions\InsufficientStock;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\StockService;
use App\Support\CatalogSearch;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->when($request->boolean('bajo'), fn ($q) => $q->lowStock())
            ->when($request->query('q'), fn ($q, $s) => $q->where('search_key', 'like', '%'.CatalogSearch::normalize($s).'%'))
            ->orderBy('code')->paginate(50)->withQueryString();

        return view('panel.inventory.index', compact('products'));
    }

    public function show(Product $product): View
    {
        return view('panel.inventory.show', [
            'product' => $product,
            'movements' => $product->movements()->with('user')->paginate(50),
        ]);
    }

    public function entry(Request $request, Product $product, StockService $stock): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $stock->entry($product, (int) $data['quantity'], Money::parseCents($data['unit_cost']), $request->user(), $data['note'] ?? null);

        return back()->with('status', 'Entrada registrada.');
    }

    public function adjust(Request $request, Product $product, StockService $stock): RedirectResponse
    {
        $data = $request->validate([
            'delta' => ['required', 'integer', 'not_in:0', 'min:-100000', 'max:100000'],
            'reason' => ['required', 'string', 'max:255'],
        ], ['reason.required' => 'Indica el motivo del ajuste.', 'delta.not_in' => 'La cantidad no puede ser cero.']);

        try {
            $stock->adjust($product, (int) $data['delta'], $data['reason'], $request->user());
        } catch (InsufficientStock $e) {
            return back()->withErrors(['delta' => $e->getMessage()]);
        }

        return back()->with('status', 'Ajuste registrado.');
    }
}
