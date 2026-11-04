@extends('layouts.panel')

@section('title', 'Nuevo pedido')

@section('content')
<h1 class="h3">Nuevo pedido de mostrador</h1>
<form method="post" action="{{ url('/panel/pedidos') }}" class="panel-form">
    @csrf
    <input type="hidden" name="form_token" value="{{ \Illuminate\Support\Str::uuid() }}">
    <h2 class="form-section-title form-section-first">Cliente</h2>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="name">Nombre</label><input class="form-control" id="name" name="name" value="{{ old('name') }}" autocomplete="off" required></div>
        <div class="col-md-6"><label class="form-label" for="phone">Teléfono</label><input class="form-control" id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="0412-123-4567" autocomplete="off" required></div>
    </div>
    @include('panel.partials.line-items')
    <h2 class="form-section-title">Detalles</h2>
    <label class="form-label" for="note">Nota (opcional)</label>
    <textarea class="form-control" id="note" name="note" rows="2">{{ old('note') }}</textarea>
    <div class="form-check choice mt-3">
        <input class="form-check-input" id="venta_inmediata" type="checkbox" name="venta_inmediata" value="1" checked>
        <label class="form-check-label" for="venta_inmediata"><strong>Venta inmediata</strong><span>Descuenta existencias, marca entregado y crea la nota de entrega.</span></label>
    </div>
    <button class="btn btn-brand mt-3">Registrar</button>
</form>
@endsection
