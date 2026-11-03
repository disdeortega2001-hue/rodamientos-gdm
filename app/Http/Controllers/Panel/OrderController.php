<?php

namespace App\Http\Controllers\Panel;

use App\Enums\OrderStatus;
use App\Exceptions\InsufficientStock;
use App\Exceptions\InvalidTransition;
use App\Exceptions\MissingPrice;
use App\Http\Controllers\Controller;
use App\Http\Requests\CounterOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::with(['customer', 'items', 'payments'])
            ->when(OrderStatus::tryFrom((string) $request->query('estado')), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('q'), function ($q, $search) {
                $q->where(fn ($w) => $w->where('number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")));
            })
            ->latest('id')->paginate(30)->withQueryString();

        return view('panel.orders.index', compact('orders'));
    }

    public function create(): View
    {
        return view('panel.orders.create', ['products' => Product::orderBy('code')->get()]);
    }

    public function store(CounterOrderRequest $request, OrderService $orders): RedirectResponse
    {
        $data = $request->validated();
        $order = $orders->create($data['items'], Customer::fromStaff($data['name'], $data['phone']), 'counter', $data['note'] ?? null);

        return redirect('/panel/pedidos/'.$order->id)->with('status', 'Pedido creado.');
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'items', 'payments.user']);

        return view('panel.orders.show', compact('order'));
    }

    public function confirm(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        return $this->act(fn () => $orders->confirm($order, $request->user()), 'Pedido confirmado. Las existencias ya se descontaron.');
    }

    public function deliver(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        return $this->act(fn () => $orders->deliver($order, $request->user()), 'Pedido marcado como entregado.');
    }

    public function cancel(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        return $this->act(fn () => $orders->cancel($order, $request->user()), 'Pedido cancelado.');
    }

    public function updateLine(Request $request, Order $order, OrderItem $item): RedirectResponse
    {
        abort_unless($item->order_id === $order->id, 404);

        $data = $request->validate(['price' => ['required', 'numeric', 'min:0', 'max:9999999']]);

        if ($order->status !== OrderStatus::New) {
            return back()->withErrors(['order' => 'Solo se puede cambiar el precio de un pedido nuevo.']);
        }

        $item->update(['unit_price_cents' => Money::parseCents($data['price'])]);

        return back()->with('status', 'Precio actualizado.');
    }

    private function act(callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (InsufficientStock|InvalidTransition|MissingPrice $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', $message);
    }
}
