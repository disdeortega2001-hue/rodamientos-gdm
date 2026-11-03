@extends('layouts.panel')

@section('title', 'Ajustes')

@section('content')
<div class="panel-title-row">
    <h1 class="h3">Ajustes</h1>
    <div class="order-actions">
        <a class="btn btn-outline-brand" href="{{ url('/panel/importar') }}">Importar Excel inicial</a>
        <a class="btn btn-brand" href="{{ url('/panel/respaldo') }}">Descargar respaldo</a>
    </div>
</div>
<form method="post" action="{{ url('/panel/ajustes') }}" class="panel-form">
    @csrf @method('put')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="usd_rate">Tasa del dólar (Bs. por $)</label>
            <input class="form-control" id="usd_rate" name="usd_rate" inputmode="decimal" value="{{ old('usd_rate', $values['usd_rate']) }}">
            <div class="form-text">Déjala vacía para mostrar los precios solo en dólares.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="whatsapp_number">WhatsApp (con código de país)</label>
            <input class="form-control" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $values['whatsapp_number']) }}" required>
        </div>
        <div class="col-md-6"><label class="form-label" for="business_name">Nombre del negocio</label><input class="form-control" id="business_name" name="business_name" value="{{ old('business_name', $values['business_name']) }}" required></div>
        <div class="col-md-6"><label class="form-label" for="city">Ciudad</label><input class="form-control" id="city" name="city" value="{{ old('city', $values['city']) }}" required></div>
        <div class="col-md-6"><label class="form-label" for="rif">RIF</label><input class="form-control" id="rif" name="rif" value="{{ old('rif', $values['rif']) }}"></div>
        <div class="col-md-6"><label class="form-label" for="hours">Horario</label><input class="form-control" id="hours" name="hours" value="{{ old('hours', $values['hours']) }}"></div>
        <div class="col-12"><label class="form-label" for="address">Dirección</label><input class="form-control" id="address" name="address" value="{{ old('address', $values['address']) }}"></div>
        <div class="col-12"><label class="form-label" for="payment_methods">Formas de pago</label><input class="form-control" id="payment_methods" name="payment_methods" value="{{ old('payment_methods', $values['payment_methods']) }}"></div>
        <div class="col-12"><label class="form-label" for="home_text">Texto del inicio</label><textarea class="form-control" id="home_text" name="home_text" rows="2">{{ old('home_text', $values['home_text']) }}</textarea></div>
    </div>
    <button class="btn btn-brand mt-3">Guardar ajustes</button>
</form>
@endsection
