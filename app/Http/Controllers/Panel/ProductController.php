<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\QuoteItem;
use App\Models\StockMovement;
use App\Support\Money;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with('category')
            ->when($request->query('q'), fn ($q, $s) => $q->where('search_key', 'like', '%'.\App\Support\CatalogSearch::normalize($s).'%'))
            ->orderBy('code')->paginate(50)->withQueryString();

        return view('panel.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('panel.products.form', ['product' => new Product, 'categories' => Category::orderBy('position')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::create($this->validated($request));

        return redirect('/panel/productos/'.$product->id.'/editar')->with('status', 'Producto creado. Usa Inventario para registrar sus existencias.');
    }

    public function edit(Product $product): View
    {
        $product->load('images');

        return view('panel.products.form', ['product' => $product, 'categories' => Category::orderBy('position')->get()]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return redirect('/panel/productos/'.$product->id.'/editar')->with('status', 'Producto actualizado.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $hasHistory = OrderItem::where('product_id', $product->id)->exists()
            || QuoteItem::where('product_id', $product->id)->exists()
            || StockMovement::where('product_id', $product->id)->exists();

        if ($hasHistory) {
            $product->update(['is_visible' => false]);

            return redirect('/panel/productos')->with('status', 'El producto tiene historial, así que se ocultó en lugar de borrarse.');
        }

        $product->images->each(fn (ProductImage $i) => $this->deleteFile($i));
        $product->delete();

        return redirect('/panel/productos')->with('status', 'Producto borrado.');
    }

    public function storeImage(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'credit_author' => ['nullable', 'string', 'max:255'],
            'credit_license' => ['nullable', 'string', 'max:255'],
            'credit_url' => ['nullable', 'url', 'max:500'],
        ], ['image.mimes' => 'La foto debe ser JPG, PNG o WebP.', 'image.max' => 'La foto no puede pesar más de 4 MB.']);

        $product->images()->create([
            'path' => $request->file('image')->store('products', 'public'),
            'is_reference' => $request->boolean('is_reference'),
            'credit_author' => $data['credit_author'] ?? null,
            'credit_license' => $data['credit_license'] ?? null,
            'credit_url' => $data['credit_url'] ?? null,
            'position' => (int) $product->images()->max('position') + 1,
        ]);

        return back()->with('status', 'Foto agregada.');
    }

    public function destroyImage(ProductImage $image): RedirectResponse
    {
        $this->deleteFile($image);
        $image->delete();

        return back()->with('status', 'Foto borrada.');
    }

    private function deleteFile(ProductImage $image): void
    {
        if (! str_starts_with($image->path, 'img/')) {
            Storage::disk('public')->delete($image->path);
        }
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:100', Rule::unique('products', 'code')->ignore($product?->id),
                function (string $attr, mixed $value, Closure $fail) use ($product) {
                    $exists = Product::where('slug', Str::slug((string) $value))->when($product, fn ($q) => $q->whereKeyNot($product->id))->exists();
                    if ($exists) {
                        $fail('Ya existe un producto con un código equivalente.');
                    }
                }],
            'measures' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:500'],
            'brand' => ['nullable', 'string', 'max:100'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
        ], [
            'code.unique' => 'Ya existe un producto con ese código.',
            'price.min' => 'El precio no puede ser negativo.',
        ]);

        $data['price_cents'] = Money::parseCents($data['price'] ?? null);
        unset($data['price']);

        return $data + [
            'track_stock' => $request->boolean('track_stock'),
            'is_visible' => $request->boolean('is_visible'),
            'is_featured' => $request->boolean('is_featured'),
        ];
    }
}
