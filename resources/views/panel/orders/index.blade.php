@extends('layouts.panel')

@section('title', 'Pedidos')

@section('content')
<div class="panel-title-row">
    <h1 class="h3">Pedidos</h1>
    <a class="btn btn-brand" href="{{ url('/panel/pedidos/nuevo') }}">Nuevo pedido</a>
</div>
<form class="panel-search" method="get">
    <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Número, cliente o teléfono" aria-label="Buscar">
    <select class="form-select" name="estado" aria-label="Estado">
        <option value="">Todos los estados</option>
        @foreach (\App\Enums\OrderStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected(request('estado') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <button class="btn btn-outline-brand">Filtrar</button>
</form>
<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Pedido</th><th>Fecha</th><th>Cliente</th><th class="text-end">Total</th><th>Estado</th><th>Pago</th></tr></thead>
        <tbody>
        @forelse ($orders as $order)
            <tr>
                <td><a href="{{ url('/panel/pedidos/'.$order->id) }}"><strong>{{ $order->number }}</strong></a></td>
                <td>{{ $order->created_at->format('d/m/Y') }}</td>
                <td>{{ $order->customer?->name ?? 'Historial del Excel' }}</td>
                <td class="text-end">{{ $order->totalCents() === null ? 'Por confirmar' : \App\Support\Money::usd($order->totalCents()) }}</td>
                <td><span class="status status-{{ $order->status->value }}">{{ $order->status->label() }}</span></td>
                <td>{{ $order->paymentStatus()->label() }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No hay pedidos con ese filtro.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $orders->links('pagination::bootstrap-5') }}
@endsection
