<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Rodamientos GDM | Rolineras SKF en Valle de la Pascua')</title>
    <meta name="description" content="@yield('description', 'Rolineras y rodamientos SKF en Valle de la Pascua, Guárico. Busca por código o medida y envía tu pedido por WhatsApp.')">
    <meta name="theme-color" content="#14284B">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
@php
    $city = \App\Models\Setting::get('city', 'Valle de la Pascua, Guárico');
    $rate = \App\Models\Setting::usdRate();
    $localPhone = \App\Support\Phone::normalize(\App\Models\Setting::get('whatsapp_number', '584129477195'));
    $phoneText = strlen($localPhone) === 11 ? substr($localPhone, 0, 4).'-'.substr($localPhone, 4, 3).'-'.substr($localPhone, 7) : $localPhone;
    $contactUrl = \App\Support\WhatsApp::url('Hola, Rodamientos GDM. Quiero hacer una consulta.');
@endphp
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <div class="top-bar">
        <div class="container top-bar-inner">
            <span class="top-item"><x-icon name="pin" /> {{ $city }}</span>
            <a class="top-item" href="{{ $contactUrl }}" rel="noopener"><x-icon name="chat" /> {{ $phoneText }}</a>
            @if ($rate)
                <span class="top-item top-rate">{{ \App\Models\Setting::get('usd_rate_auto', '1') === '1' && config('gdm.bcv_url') ? 'Tasa BCV' : 'Tasa' }}: Bs. {{ number_format((float) $rate, 2, ',', '.') }} por $</span>
            @endif
        </div>
    </div>
    <header class="site-header">
        <div class="container site-nav">
            <x-logo />
            <form class="header-search" method="get" action="{{ route('catalog.index') }}" role="search">
                <label class="visually-hidden" for="header-q">Buscar en el catálogo</label>
                <input class="form-control" id="header-q" type="search" name="q" placeholder="Buscar por código o medida">
                <button class="header-search-btn" aria-label="Buscar"><x-icon name="search" /></button>
            </form>
            <nav class="site-links" aria-label="Principal">
                <a class="site-link" href="{{ route('catalog.index') }}" @if (request()->is('catalogo*')) aria-current="page" @endif>Catálogo</a>
                <a class="site-link" href="{{ url('/guia') }}" @if (request()->is('guia')) aria-current="page" @endif>Guía</a>
                <a class="site-link" href="{{ url('/empresa') }}" @if (request()->is('empresa')) aria-current="page" @endif>Empresa</a>
            </nav>
            <a class="cart-link" href="{{ url('/carrito') }}"><x-icon name="cart" /><span class="cart-label">Mi pedido</span> <span class="cart-count" data-cart-count>0</span></a>
        </div>
    </header>
    <main id="contenido">
        @yield('content')
    </main>
    <footer class="site-footer">
        <div class="container footer-inner">
            <div class="footer-brand">
                <x-logo />
                <p>Rolineras, crucetas y estoperas para automoción, maquinaria e industria.</p>
                <p class="footer-note">Precios en dólares, sujetos a confirmación.</p>
            </div>
            <div>
                <h2 class="footer-title">Portal</h2>
                <ul class="footer-links">
                    <li><a href="{{ route('catalog.index') }}">Catálogo</a></li>
                    <li><a href="{{ url('/guia') }}">Guía de medidas</a></li>
                    <li><a href="{{ url('/empresa') }}">Empresa y contacto</a></li>
                    <li><a href="{{ url('/carrito') }}">Mi pedido</a></li>
                </ul>
            </div>
            <div>
                <h2 class="footer-title">Contacto</h2>
                <ul class="footer-links">
                    <li><a href="{{ $contactUrl }}" rel="noopener">WhatsApp: {{ $phoneText }}</a></li>
                    <li>{{ $city }}</li>
                </ul>
            </div>
            <div class="footer-legal">
                <a href="{{ url('/creditos') }}">Créditos de imágenes</a>
                <a href="{{ url('/panel/acceso') }}" rel="nofollow">Acceso del personal</a>
            </div>
        </div>
    </footer>
    <a class="whatsapp-float" href="{{ $contactUrl }}" rel="noopener" aria-label="Escribir por WhatsApp"><x-icon name="chat" /></a>
    <div class="toast-cart" data-cart-toast role="status" hidden>
        <x-icon name="check" /> <span data-cart-toast-text></span>
        <a href="{{ url('/carrito') }}">Ver pedido</a>
    </div>
    <script src="{{ asset('js/cart.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
