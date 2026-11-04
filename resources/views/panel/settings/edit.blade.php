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
    @if (config('gdm.portal_private'))
        <div class="form-check choice mb-3">
            <input class="form-check-input" id="portal_public" type="checkbox" name="portal_public" value="1" @checked(\App\Models\Setting::get('portal_public') === '1')>
            <label class="form-check-label" for="portal_public"><strong>Abrir el portal al público</strong><span>Sin marcar, los visitantes ven "Próximamente" y solo el personal con sesión iniciada ve el catálogo.</span></label>
        </div>
    @endif

    <h2 class="form-section-title">Tasa del dólar</h2>
    <div class="row g-3">
        <div class="col-md-5">
            <label class="form-label" for="usd_rate">Bs. por dólar</label>
            <input class="form-control" id="usd_rate" name="usd_rate" inputmode="decimal" value="{{ old('usd_rate', $values['usd_rate']) }}">
        </div>
        <div class="col-md-7 align-self-end">
            <div class="form-check choice">
                <input class="form-check-input" id="usd_rate_auto" type="checkbox" name="usd_rate_auto" value="1" @checked($autoRate)>
                <label class="form-check-label" for="usd_rate_auto"><strong>Usar la tasa oficial del BCV automáticamente</strong><span>Desmárcala para escribir la tasa a mano. Si la dejas vacía, los precios salen solo en dólares.</span></label>
            </div>
        </div>
    </div>

    <h2 class="form-section-title">Datos del negocio</h2>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="business_name">Nombre del negocio</label><input class="form-control" id="business_name" name="business_name" value="{{ old('business_name', $values['business_name']) }}" required></div>
        <div class="col-md-6"><label class="form-label" for="rif">RIF</label><input class="form-control" id="rif" name="rif" value="{{ old('rif', $values['rif']) }}" placeholder="J-12345678-9"></div>
    </div>

    <h2 class="form-section-title">Contacto</h2>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="whatsapp_number">WhatsApp, con código de país</label>
            <input class="form-control" id="whatsapp_number" name="whatsapp_number" inputmode="numeric" value="{{ old('whatsapp_number', $values['whatsapp_number']) }}" required>
        </div>
        <div class="col-md-6"><label class="form-label" for="city">Ciudad</label><input class="form-control" id="city" name="city" value="{{ old('city', $values['city']) }}" required></div>
        <div class="col-md-6"><label class="form-label" for="address">Dirección</label><input class="form-control" id="address" name="address" value="{{ old('address', $values['address']) }}"></div>
        <div class="col-md-6"><label class="form-label" for="hours">Horario</label><input class="form-control" id="hours" name="hours" value="{{ old('hours', $values['hours']) }}"></div>
        <div class="col-md-6"><label class="form-label" for="instagram">Instagram</label><input class="form-control" id="instagram" name="instagram" value="{{ old('instagram', $values['instagram']) }}" placeholder="rodamientogdm" autocomplete="off"></div>
        <div class="col-md-6"><label class="form-label" for="payment_methods">Formas de pago</label><input class="form-control" id="payment_methods" name="payment_methods" value="{{ old('payment_methods', $values['payment_methods']) }}" placeholder="Pago móvil, transferencia, efectivo"></div>
    </div>

    <h2 class="form-section-title">Texto del inicio</h2>
    <label class="visually-hidden" for="home_text">Texto del inicio</label>
    <textarea class="form-control" id="home_text" name="home_text" rows="2" placeholder="Si lo dejas vacío, se usa el texto predeterminado.">{{ old('home_text', $values['home_text']) }}</textarea>

    <button class="btn btn-brand mt-3">Guardar ajustes</button>
</form>
@endsection
