@extends('layouts.panel')

@section('title', 'Productos')

@section('content')
<div class="panel-title-row">
    <h1 class="h3">Productos</h1>
    @can('manage-all')<a class="btn btn-brand" href="{{ url('/panel/productos/nuevo') }}">Nuevo producto</a>@endcan
</div>
<form class="panel-search" method="get">
    <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por código o medida" aria-label="Buscar">
    <button class="btn btn-outline-brand">Buscar</button>
</form>
<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr>
            <th>Código</th><th>Categoría</th><th>Medidas</th><th class="text-end">Precio</th>
            @can('manage-all')<th class="text-end">Costo</th>@endcan
            <th class="text-end">Existencia</th><th>Estado</th>
            @can('manage-all')<th></th>@endcan
        </tr></thead>
        <tbody>
        @foreach ($products as $product)
            <tr>
                <td><strong>{{ $product->code }}</strong></td>
                <td>{{ $product->category->name }}</td>
                <td>{{ $product->measures }}</td>
                <td class="text-end">{{ $product->price_cents === null ? 'Consultar' : \App\Support\Money::usd($product->price_cents) }}</td>
                @can('manage-all')<td class="text-end">{{ \App\Support\Money::usd($product->avg_cost_cents) }}</td>@endcan
                <td class="text-end">{{ $product->track_stock ? $product->stock : '—' }}</td>
                <td><span class="pill {{ $product->is_visible ? 'pill-ok' : 'pill-off' }}">{{ $product->is_visible ? 'Visible' : 'Oculto' }}</span></td>
                @can('manage-all')<td class="text-end"><a class="btn btn-sm btn-outline-brand" href="{{ url('/panel/productos/'.$product->id.'/editar') }}">Editar</a></td>@endcan
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $products->links('pagination::bootstrap-5') }}
@endsection
