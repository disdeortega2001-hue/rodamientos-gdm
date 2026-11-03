<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Public\PublicProduct;
use App\Services\OrderService;
use App\Support\Money;
use App\Support\OrderMessage;
use App\Support\WhatsApp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, OrderService $orders): JsonResponse
    {
        $data = $request->validated();
        $customer = Customer::findOrCreateByPhone($data['name'], $data['phone']);

        $note = $data['note'] ?? null;
        if ($customer->name !== $data['name']) {
            $note = trim('Nombre indicado: '.$data['name'].'. '.$note);
        }

        $items = array_map(fn (array $i) => Arr::only($i, ['product_id', 'quantity']), $data['items']);
        $order = $orders->create($items, $customer, 'web', $note, $data['token']);

        return response()->json([
            'number' => $order->number,
            'whatsapp_url' => WhatsApp::url(OrderMessage::for($order, $data['name'])),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $lines = collect($request->input('items', []))
            ->filter(fn ($i) => is_array($i) && isset($i['product_id']))
            ->take(50)
            ->map(fn (array $i) => ['product_id' => (int) $i['product_id'], 'quantity' => max(1, min(999, (int) ($i['quantity'] ?? 1)))]);

        $products = Product::visible()->with(['images', 'category'])
            ->whereIn('id', $lines->pluck('product_id'))->get()->keyBy('id');
        $rate = Setting::usdRate();
        $total = 0;
        $items = [];
        $missing = [];

        foreach ($lines as $line) {
            $product = $products->get($line['product_id']);
            if (! $product) {
                $missing[] = $line['product_id'];

                continue;
            }

            $lineCents = $product->price_cents === null ? null : $product->price_cents * $line['quantity'];
            $total = $total === null || $lineCents === null ? null : $total + $lineCents;
            $items[] = PublicProduct::fromModel($product, $rate)->toArray() + [
                'quantity' => $line['quantity'],
                'line_text' => $lineCents === null ? 'Consultar precio' : Money::usd($lineCents),
            ];
        }

        return response()->json([
            'items' => $items,
            'missing' => $missing,
            'total_text' => $total === null ? 'por confirmar' : Money::usd($total),
            'total_bs_text' => $total !== null && $rate !== null ? Money::bs($total, $rate) : null,
        ]);
    }
}
