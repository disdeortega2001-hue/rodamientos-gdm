@extends('layouts.panel')

@section('title', 'Notas de entrega')

@section('content')
<h1 class="h3">Notas de entrega</h1>
<form class="panel-search" method="get">
    <input class="form-control" type="search" name="q" value="{{ is_string(request('q')) ? request('q') : '' }}" placeholder="Nota, pedido, cliente o teléfono" aria-label="Buscar">
    <button class="btn btn-outline-brand">Buscar</button>
</form>
<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Nota</th><th>Fecha</th><th>Cliente</th><th>Pedido</th><th class="text-end">Total</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($orders as $order)
            <tr>
                <td><a href="{{ url('/nota/'.$order->delivery_note_token) }}"><strong>{{ $order->delivery_note_number }}</strong></a></td>
                <td>{{ $order->delivery_note_issued_at?->format('d/m/Y') }}</td>
                <td>{{ $order->customer?->name }}</td>
                <td><a href="{{ url('/panel/pedidos/'.$order->id) }}">{{ $order->number }}</a></td>
                <td class="text-end">{{ \App\Support\Money::usd((int) $order->totalCents()) }}</td>
                <td><span class="status status-{{ $order->status->value }}">{{ $order->status === \App\Enums\OrderStatus::Cancelled ? 'Anulada' : $order->status->label() }}</span></td>
                <td class="text-end"><a href="{{ url('/nota/'.$order->delivery_note_token.'/pdf') }}">PDF</a></td>
            </tr>
        @empty
            <tr><td colspan="7">Todavía no hay notas. Se crean al confirmar una venta.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $orders->links('pagination::bootstrap-5') }}
@endsection
