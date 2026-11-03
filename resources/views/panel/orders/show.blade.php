@extends('layouts.panel')

@section('title', 'Pedido '.$order->number)

@section('content')
@php($total = $order->totalCents())
@php($isAdmin = auth()->user()->can('manage-all'))
<nav><a href="{{ url('/panel/pedidos') }}">Volver a pedidos</a></nav>
<div class="panel-title-row mt-2">
    <h1 class="h3">Pedido {{ $order->number }} <span class="status status-{{ $order->status->value }}">{{ $order->status->label() }}</span></h1>
    <div class="order-actions">
        @if ($order->status === \App\Enums\OrderStatus::New)
            <form method="post" action="{{ url('/panel/pedidos/'.$order->id.'/confirmar') }}">@csrf<button class="btn btn-brand">Confirmar</button></form>
        @endif
        @if ($order->status === \App\Enums\OrderStatus::Confirmed)
            <form method="post" action="{{ url('/panel/pedidos/'.$order->id.'/entregar') }}">@csrf<button class="btn btn-brand">Marcar entregado</button></form>
        @endif
        @if (in_array($order->status, [\App\Enums\OrderStatus::New, \App\Enums\OrderStatus::Confirmed], true))
            <form method="post" action="{{ url('/panel/pedidos/'.$order->id.'/cancelar') }}" onsubmit="return confirm('¿Cancelar este pedido?')">@csrf<button class="btn btn-outline-danger">Cancelar pedido</button></form>
        @endif
    </div>
</div>

<div class="panel-card">
    @if ($order->customer)
        <p class="mb-1"><strong>Cliente:</strong> <a href="{{ url('/panel/clientes/'.$order->customer_id) }}">{{ $order->customer->name }}</a> · {{ $order->customer->phone }}</p>
    @endif
    <p class="mb-1"><strong>Fecha:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
    @if ($order->usd_rate)<p class="mb-1"><strong>Tasa:</strong> Bs. {{ number_format((float) $order->usd_rate, 2, ',', '.') }} por dólar</p>@endif
    @if ($order->note)<p class="mb-0"><strong>Nota:</strong> {{ $order->note }}</p>@endif
</div>

<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr>
            <th>Pieza</th><th>Medidas</th><th class="text-end">Cantidad</th><th class="text-end">Precio</th><th class="text-end">Subtotal</th>
            @if ($isAdmin)<th class="text-end">Costo</th><th class="text-end">Ganancia</th>@endif
        </tr></thead>
        <tbody>
        @foreach ($order->items as $item)
            <tr>
                <td><strong>{{ $item->code }}</strong></td>
                <td>{{ $item->measures }}</td>
                <td class="text-end">{{ $item->quantity }}</td>
                <td class="text-end">
                    @if ($order->status === \App\Enums\OrderStatus::New)
                        <form method="post" action="{{ url('/panel/pedidos/'.$order->id.'/lineas/'.$item->id) }}" class="line-price">
                            @csrf @method('put')
                            <input class="form-control form-control-sm" name="price" inputmode="decimal" value="{{ \App\Support\Money::input($item->unit_price_cents) }}" aria-label="Precio de {{ $item->code }}" required>
                            <button class="btn btn-sm btn-outline-brand">Guardar</button>
                        </form>
                    @else
                        {{ \App\Support\Money::usd((int) $item->unit_price_cents) }}
                    @endif
                </td>
                <td class="text-end">{{ $item->unit_price_cents === null ? 'Por confirmar' : \App\Support\Money::usd($item->quantity * $item->unit_price_cents) }}</td>
                @if ($isAdmin)
                    <td class="text-end">{{ \App\Support\Money::usd($item->unit_cost_cents) }}</td>
                    <td class="text-end">{{ $item->unit_price_cents === null ? '—' : \App\Support\Money::usd($item->quantity * ($item->unit_price_cents - $item->unit_cost_cents)) }}</td>
                @endif
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr><th colspan="4" class="text-end">Total</th><th class="text-end">{{ $total === null ? 'Por confirmar' : \App\Support\Money::usd($total) }}</th>@if ($isAdmin)<th colspan="2"></th>@endif</tr></tfoot>
    </table>
</div>

<h2 class="h5 mt-4">Pagos <small class="text-body-secondary">{{ $order->paymentStatus()->label() }} · {{ \App\Support\Money::usd($order->paidCents()) }} recibidos</small></h2>
<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Fecha</th><th>Forma</th><th>Referencia</th><th class="text-end">Monto</th><th>Registró</th>@if ($isAdmin)<th></th>@endif</tr></thead>
        <tbody>
        @forelse ($order->payments as $payment)
            <tr>
                <td>{{ $payment->paid_at->format('d/m/Y') }}</td>
                <td>{{ $payment->method->label() }}</td>
                <td>{{ $payment->reference }}</td>
                <td class="text-end">{{ \App\Support\Money::usd($payment->amount_cents) }}</td>
                <td>{{ $payment->user?->name ?? '—' }}</td>
                @if ($isAdmin)
                    <td class="text-end">
                        <form method="post" action="{{ url('/panel/pagos/'.$payment->id) }}" onsubmit="return confirm('¿Eliminar este pago?')">@csrf @method('delete')<button class="btn btn-sm btn-link text-danger">Eliminar</button></form>
                    </td>
                @endif
            </tr>
        @empty
            <tr><td colspan="6">Todavía no hay pagos.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@if ($order->status !== \App\Enums\OrderStatus::Cancelled && $order->paymentStatus() !== \App\Enums\PaymentStatus::Paid)
<form method="post" action="{{ url('/panel/pedidos/'.$order->id.'/pagos') }}" class="panel-form">
    @csrf
    <input type="hidden" name="form_token" value="{{ \Illuminate\Support\Str::uuid() }}">
    <h3 class="h6">Registrar pago</h3>
    <div class="row g-2">
        <div class="col-md-3"><label class="form-label" for="method">Forma</label>
            <select class="form-select" id="method" name="method">
                @foreach (\App\Enums\PaymentMethod::cases() as $method)
                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><label class="form-label" for="reference">Referencia</label><input class="form-control" id="reference" name="reference" maxlength="100"></div>
        <div class="col-md-3"><label class="form-label" for="amount">Monto ($)</label><input class="form-control" id="amount" name="amount" inputmode="decimal" required></div>
        <div class="col-md-3"><label class="form-label" for="paid_at">Fecha</label><input class="form-control" id="paid_at" type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}" required></div>
    </div>
    <button class="btn btn-outline-brand mt-3">Registrar pago</button>
</form>
@endif
@endsection
