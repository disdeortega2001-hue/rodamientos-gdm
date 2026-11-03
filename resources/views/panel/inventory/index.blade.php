@extends('layouts.panel')

@section('title', 'Inventario')

@section('content')
<div class="panel-title-row">
    <h1 class="h3">Inventario</h1>
    <a class="btn btn-outline-brand" href="{{ request()->boolean('bajo') ? url('/panel/inventario') : url('/panel/inventario?bajo=1') }}">{{ request()->boolean('bajo') ? 'Ver todos' : 'Ver pocas unidades' }}</a>
</div>
<form class="panel-search" method="get">
    <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por código o medida" aria-label="Buscar">
    <button class="btn btn-outline-brand">Buscar</button>
</form>
<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr>
            <th>Código</th><th>Medidas</th><th class="text-end">Existencia</th><th>Estado</th>
            @can('manage-all')<th class="text-end">Costo promedio</th>@endcan
            <th></th>
        </tr></thead>
        <tbody>
        @foreach ($products as $product)
            <tr>
                <td><strong>{{ $product->code }}</strong></td>
                <td>{{ $product->measures }}</td>
                <td class="text-end">{{ $product->track_stock ? $product->stock : '—' }}</td>
                <td><span class="availability availability-{{ $product->availability()->value }}">{{ $product->availability()->label() }}</span></td>
                @can('manage-all')<td class="text-end">{{ \App\Support\Money::usd($product->avg_cost_cents) }}</td>@endcan
                <td class="text-end"><a href="{{ url('/panel/inventario/'.$product->id) }}">{{ auth()->user()->can('manage-all') ? 'Registrar' : 'Historial' }}</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $products->links('pagination::bootstrap-5') }}
@endsection
