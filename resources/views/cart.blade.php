@extends('layouts.public')

@section('title', 'Mi pedido | Rodamientos GDM')

@section('content')
<section class="container page-section">
    <h1 class="page-title">Mi pedido</h1>
    <noscript><div class="alert alert-warning">Para armar tu pedido necesitas activar JavaScript, o escríbenos directamente por WhatsApp.</div></noscript>
    <div data-cart-page>
        <div data-cart-notice class="alert alert-warning" role="status" hidden></div>
        <div data-cart-empty class="empty-state" hidden>
            <h2>Tu pedido está vacío</h2>
            <p>Busca tu pieza por código o medida y agrégala aquí.</p>
            <a class="btn btn-accent" href="{{ route('catalog.index') }}">Ver catálogo</a>
        </div>
        <div data-cart-content class="cart-layout" hidden>
            <div>
                <ul class="cart-list" data-cart-list></ul>
                <div class="cart-summary">
                    <p class="cart-total"><span>Total estimado</span> <strong data-cart-total></strong></p>
                    <p class="cart-total-bs" data-cart-total-bs></p>
                    <p class="cart-note">Precios sujetos a confirmación. Confirmamos disponibilidad y forma de pago por WhatsApp.</p>
                </div>
            </div>
            <form class="cart-form" data-cart-form>
                <h2 class="form-section-title form-section-first">Tus datos</h2>
                <div class="mb-3">
                    <label class="form-label" for="cart-name">Nombre</label>
                    <input class="form-control" id="cart-name" name="name" autocomplete="name" required maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cart-phone">Teléfono</label>
                    <input class="form-control" id="cart-phone" name="phone" type="tel" autocomplete="tel" placeholder="0412-123-4567" required maxlength="30">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cart-note">Nota (opcional)</label>
                    <textarea class="form-control" id="cart-note" name="note" rows="2" maxlength="500"></textarea>
                </div>
                <div class="alert alert-danger" data-cart-error role="alert" hidden></div>
                <button class="btn btn-accent w-100" data-cart-submit>Enviar pedido por WhatsApp</button>
            </form>
        </div>
    </div>
</section>
@endsection
