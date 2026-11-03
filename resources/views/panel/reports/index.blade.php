@extends('layouts.panel')

@section('title', 'Finanzas')

@section('content')
<h1 class="h3">Finanzas y reportes</h1>
<form class="panel-search" method="get">
    <div><label class="form-label" for="from">Desde</label><input class="form-control" id="from" type="date" name="from" value="{{ $from->format('Y-m-d') }}"></div>
    <div><label class="form-label" for="to">Hasta</label><input class="form-control" id="to" type="date" name="to" value="{{ $to->format('Y-m-d') }}"></div>
    <button class="btn btn-outline-brand align-self-end">Ver</button>
    <a class="btn btn-brand align-self-end" href="{{ url('/panel/reportes/exportar?from='.$from->format('Y-m-d').'&to='.$to->format('Y-m-d')) }}">Exportar a Excel</a>
</form>
<div class="stat-row">
    <div class="stat"><span>Ventas</span><strong>{{ \App\Support\Money::usd($summary['sales_cents']) }}</strong></div>
    <div class="stat"><span>Costo de lo vendido</span><strong>{{ \App\Support\Money::usd($summary['cost_cents']) }}</strong></div>
    <div class="stat"><span>Ganancia</span><strong>{{ \App\Support\Money::usd($summary['profit_cents']) }}</strong></div>
</div>
<div class="table-responsive mt-3">
    <table class="table align-middle">
        <thead><tr><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Ventas</th><th class="text-end">Costo</th><th class="text-end">Ganancia</th></tr></thead>
        <tbody>
        @forelse ($rows as $row)
            <tr>
                <td><strong>{{ $row->code }}</strong></td>
                <td class="text-end">{{ $row->quantity }}</td>
                <td class="text-end">{{ \App\Support\Money::usd((int) $row->sales_cents) }}</td>
                <td class="text-end">{{ \App\Support\Money::usd((int) $row->cost_cents) }}</td>
                <td class="text-end">{{ \App\Support\Money::usd((int) $row->profit_cents) }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No hay ventas en este período.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
