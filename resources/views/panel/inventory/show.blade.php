@extends('layouts.panel')

@section('title', 'Inventario de '.$product->code)

@section('content')
<nav><a href="{{ url('/panel/inventario') }}">Volver al inventario</a></nav>
<h1 class="h3 mt-2">{{ $product->code }}</h1>
<p>Existencia actual: <strong>{{ $product->track_stock ? $product->stock : 'Sin control de existencias' }}</strong>
    @can('manage-all') · Costo promedio: <strong>{{ \App\Support\Money::usd($product->avg_cost_cents) }}</strong>@endcan
</p>

@can('manage-all')
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <form method="post" action="{{ url('/panel/inventario/'.$product->id.'/entradas') }}" class="panel-form">
            @csrf
            <h2 class="h5">Registrar entrada</h2>
            <div class="row g-2">
                <div class="col-6"><label class="form-label" for="quantity">Cantidad</label><input class="form-control" id="quantity" type="number" name="quantity" min="1" required></div>
                <div class="col-6"><label class="form-label" for="unit_cost">Costo unitario ($)</label><input class="form-control" id="unit_cost" name="unit_cost" inputmode="decimal" required></div>
                <div class="col-12"><label class="form-label" for="note">Nota</label><input class="form-control" id="note" name="note" maxlength="255"></div>
            </div>
            <button class="btn btn-brand mt-3">Registrar entrada</button>
        </form>
    </div>
    <div class="col-md-6">
        <form method="post" action="{{ url('/panel/inventario/'.$product->id.'/ajustes') }}" class="panel-form">
            @csrf
            <h2 class="h5">Ajustar existencias</h2>
            <div class="row g-2">
                <div class="col-6"><label class="form-label" for="delta">Cantidad (+ o −)</label><input class="form-control" id="delta" type="number" name="delta" required></div>
                <div class="col-12"><label class="form-label" for="reason">Motivo</label><input class="form-control" id="reason" name="reason" maxlength="255" required></div>
            </div>
            <button class="btn btn-outline-brand mt-3">Guardar ajuste</button>
        </form>
    </div>
</div>
@endcan

<h2 class="h5">Historial</h2>
<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr>
            <th>Fecha</th><th>Tipo</th><th class="text-end">Cantidad</th>
            @can('manage-all')<th class="text-end">Costo</th>@endcan
            <th>Usuario</th><th>Nota</th>
        </tr></thead>
        <tbody>
        @forelse ($movements as $movement)
            <tr>
                <td>{{ $movement->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                <td>{{ $movement->type instanceof \App\Enums\MovementType ? $movement->type->label() : ($movement->type ?? '—') }}</td>
                <td class="text-end">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td>
                @can('manage-all')<td class="text-end">{{ $movement->unit_cost_cents === null ? '—' : \App\Support\Money::usd($movement->unit_cost_cents) }}</td>@endcan
                <td>{{ $movement->user?->name ?? '—' }}</td>
                <td>{{ $movement->note }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ auth()->user()->can('manage-all') ? 6 : 5 }}">Todavía no hay movimientos.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $movements->links('pagination::bootstrap-5') }}
@endsection
