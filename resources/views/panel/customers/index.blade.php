@extends('layouts.panel')

@section('title', 'Clientes')

@section('content')
<h1 class="h3">Clientes</h1>
<form class="panel-search" method="get">
    <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Nombre o teléfono" aria-label="Buscar">
    <button class="btn btn-outline-brand">Buscar</button>
</form>
<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Nombre</th><th>Teléfono</th><th class="text-end">Pedidos</th></tr></thead>
        <tbody>
        @forelse ($customers as $customer)
            <tr><td><a href="{{ url('/panel/clientes/'.$customer->id) }}">{{ $customer->name }}</a></td><td>{{ $customer->phone }}</td><td class="text-end">{{ $customer->orders_count }}</td></tr>
        @empty
            <tr><td colspan="3">No hay clientes con ese filtro.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $customers->links('pagination::bootstrap-5') }}
@endsection
