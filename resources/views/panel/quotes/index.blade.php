@extends('layouts.panel')

@section('title', 'Cotizaciones')

@section('content')
<div class="panel-title-row">
    <h1 class="h3">Cotizaciones</h1>
    <a class="btn btn-brand" href="{{ url('/panel/cotizaciones/nueva') }}">Nueva cotización</a>
</div>
<form class="panel-search" method="get">
    <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Número o cliente" aria-label="Buscar">
    <button class="btn btn-outline-brand">Buscar</button>
</form>
<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Cotización</th><th>Fecha</th><th>Cliente</th><th class="text-end">Total</th><th>Estado</th></tr></thead>
        <tbody>
        @forelse ($quotes as $quote)
            <tr>
                <td><a href="{{ url('/panel/cotizaciones/'.$quote->id) }}"><strong>{{ $quote->number }}</strong></a></td>
                <td>{{ $quote->created_at->format('d/m/Y') }}</td>
                <td>{{ $quote->customer->name }}</td>
                <td class="text-end">{{ $quote->totalCents() === null ? 'Por confirmar' : \App\Support\Money::usd($quote->totalCents()) }}</td>
                <td>{{ $quote->order_id ? 'Convertida en pedido' : 'Abierta' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Todavía no hay cotizaciones.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $quotes->links('pagination::bootstrap-5') }}
@endsection
