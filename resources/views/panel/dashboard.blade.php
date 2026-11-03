@extends('layouts.panel')

@section('title', 'Inicio')

@section('content')
<h1 class="h3">Inicio</h1>
@if ($summary)
    <div class="stat-row">
        <div class="stat"><span>Ventas del mes</span><strong>{{ \App\Support\Money::usd($summary['sales_cents']) }}</strong></div>
        <div class="stat"><span>Ganancia del mes</span><strong>{{ \App\Support\Money::usd($summary['profit_cents']) }}</strong></div>
    </div>
@endif
<div class="row g-4 mt-1">
    <div class="col-lg-7">
        <h2 class="h5">Pedidos nuevos</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Pedido</th><th>Cliente</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                @forelse ($newOrders as $order)
                    <tr>
                        <td><a href="{{ url('/panel/pedidos/'.$order->id) }}"><strong>{{ $order->number }}</strong></a></td>
                        <td>{{ $order->customer?->name }}</td>
                        <td class="text-end">{{ $order->totalCents() === null ? 'Por confirmar' : \App\Support\Money::usd($order->totalCents()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No hay pedidos nuevos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <h2 class="h5">Pocas unidades</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Producto</th><th class="text-end">Existencia</th></tr></thead>
                <tbody>
                @forelse ($lowStock as $product)
                    <tr><td><a href="{{ url('/panel/inventario/'.$product->id) }}">{{ $product->code }}</a></td><td class="text-end">{{ $product->stock }}</td></tr>
                @empty
                    <tr><td colspan="2">Todo tiene existencias suficientes.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
