<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Public\PublicProduct;
use App\Support\CatalogSearch;
use App\Support\Measures;
use App\Support\WhatsApp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::visible()->with(['images', 'category']);
        $search = trim($this->text($request, 'q'));
        $categorySlug = $this->text($request, 'categoria');
        $sort = $this->text($request, 'orden');
        $wanted = [
            'interior' => $this->number($request, 'interior'),
            'exterior' => $this->number($request, 'exterior'),
            'ancho' => $this->number($request, 'ancho'),
        ];

        $this->applySearch($query, $search);

        if ($categorySlug !== '') {
            $query->whereHas('category', fn (Builder $q) => $q->where('slug', $categorySlug));
        }

        if ($request->boolean('disponibles')) {
            $query->where(fn (Builder $q) => $q->where('stock', '>', 0)->orWhere('track_stock', false));
        }

        match ($sort) {
            'precio_asc' => $query->orderByRaw('price_cents IS NULL')->orderBy('price_cents'),
            'precio_desc' => $query->orderByRaw('price_cents IS NULL')->orderByDesc('price_cents'),
            default => $query->orderBy('code'),
        };

        $products = $query->get();
        $measureText = collect($wanted)->filter(fn ($v) => $v !== null)->implode(' x ');

        if ($measureText !== '') {
            $products = $products->filter(fn (Product $p) => Measures::matches($p->measures, $wanted))->values();
        }

        return view('catalog.index', [
            'products' => $this->present($products),
            'categories' => Category::orderBy('position')->get(),
            'search' => $search,
            'categorySlug' => $categorySlug,
            'sort' => $sort,
            'wanted' => $wanted,
            'keep' => array_filter([
                'q' => $search,
                'interior' => $wanted['interior'],
                'exterior' => $wanted['exterior'],
                'ancho' => $wanted['ancho'],
                'orden' => $sort,
                'disponibles' => $request->boolean('disponibles') ? 1 : null,
            ], fn ($v) => $v !== null && $v !== ''),
            'whatsappSearchUrl' => WhatsApp::url('Hola, Rodamiento GDM. Estoy buscando: '.trim($search.' '.$measureText)),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_visible, 404);

        $product->load(['images', 'category']);
        $related = Product::visible()->with(['images', 'category'])
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->orderByDesc('is_featured')->orderBy('code')->limit(4)->get();

        $public = PublicProduct::fromModel($product, Setting::usdRate());

        return view('catalog.show', [
            'product' => $public,
            'related' => $this->present($related),
            'whatsappUrl' => WhatsApp::url('Hola, Rodamiento GDM. Quiero consultar por '.$public->code.'.'),
        ]);
    }

    private function text(Request $request, string $key): string
    {
        $value = $request->query($key);

        return is_string($value) ? $value : '';
    }

    private function number(Request $request, string $key): ?float
    {
        $value = str_replace(',', '.', trim($this->text($request, $key)));

        return is_numeric($value) && (float) $value > 0 ? (float) $value : null;
    }

    private function applySearch(Builder $query, string $search): void
    {
        $whole = CatalogSearch::normalize($search);

        if ($whole === '') {
            return;
        }

        $words = collect(preg_split('/\s+/', $search))
            ->map(fn (string $w) => CatalogSearch::normalize($w))
            ->reject(fn (string $w) => $w === '' || $w === 'x');

        $query->where(function (Builder $q) use ($whole, $words) {
            $q->where('search_key', 'like', "%{$whole}%")
                ->orWhere(fn (Builder $all) => $words->each(fn (string $w) => $all->where('search_key', 'like', "%{$w}%")));
        });
    }

    private function present(Collection $products): Collection
    {
        $rate = Setting::usdRate();

        return $products->map(fn (Product $p) => PublicProduct::fromModel($p, $rate));
    }
}
