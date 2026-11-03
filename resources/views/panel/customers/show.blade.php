@extends('layouts.panel')

@section('title', $customer->name)

@section('content')
<nav><a href="{{ url('/panel/clientes') }}">Volver a clientes</a></nav>
<h1 class="h3 mt-2">{{ $customer->name }}</h1>
<p>{{ $customer->phone }} · <a href="https://wa.me/{{ \App\Support\Phone::international($customer->phone) }}" rel="noopener">Escribir por WhatsApp</a></p>
<form method="post" action="{{ url('/panel/clientes/'.$customer->id) }}" class="panel-form">
    @csrf @method('put')
    <div class="mb-3"><label class="form-label" for="name">Nombre</label><input class="form-control" id="name" name="name" value="{{ old('name', $customer->name) }}" required></div>
    <div class="mb-3"><label class="form-label" for="notes">Notas</label><textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes', $customer->notes) }}</textarea></div>
    <button class="btn btn-outline-brand">Guardar</button>
</form>
<h2 class="h5 mt-4">Historial de pedidos</h2>
<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Pedido</th><th>Fecha</th><th class="text-end">Total</th><th>Estado</th><th>Pago</th></tr></thead>
        <tbody>
        @forelse ($orders as $order)
            <tr>
                <td><a href="{{ url('/panel/pedidos/'.$order->id) }}"><strong>{{ $order->number }}</strong></a></td>
                <td>{{ $order->created_at->format('d/m/Y') }}</td>
                <td class="text-end">{{ $order->totalCents() === null ? 'Por confirmar' : \App\Support\Money::usd($order->totalCents()) }}</td>
                <td><span class="status status-{{ $order->status->value }}">{{ $order->status->label() }}</span></td>
                <td>{{ $order->paymentStatus()->label() }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Este cliente todavía no tiene pedidos.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
