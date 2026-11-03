@extends('layouts.public')

@section('content')
<section class="hero">
    <img class="hero-photo" src="{{ asset('img/site/portada.webp') }}" alt="Rolinera de bolas sobre una superficie metálica" width="1400" height="728" fetchpriority="high">
    <div class="container hero-inner">
        <h1>Rolineras SKF en Valle de la Pascua</h1>
        <p class="hero-lead">{{ $homeText ?: 'Busca tu pieza por código o medida y envía tu pedido por WhatsApp.' }}</p>
        <div class="hero-actions">
            <a class="btn btn-accent btn-lg" href="{{ route('catalog.index') }}">Ver catálogo <x-icon name="arrow" /></a>
            <a class="btn btn-ghost btn-lg" href="{{ $whatsappUrl }}" rel="noopener"><x-icon name="chat" /> Escribir por WhatsApp</a>
        </div>
    </div>
</section>

<section class="container finder-wrap" aria-labelledby="finder-title">
    <form class="finder" method="get" action="{{ route('catalog.index') }}">
        <div class="finder-head">
            <x-icon name="ruler" />
            <div>
                <h2 id="finder-title">Encuentra tu pieza por medida</h2>
                <p>Escribe las medidas que conozcas, en milímetros.</p>
            </div>
        </div>
        <div class="finder-fields">
            <div><label class="form-label" for="f-interior">Interior</label><input class="form-control" id="f-interior" name="interior" inputmode="decimal" placeholder="20"></div>
            <div><label class="form-label" for="f-exterior">Exterior</label><input class="form-control" id="f-exterior" name="exterior" inputmode="decimal" placeholder="47"></div>
            <div><label class="form-label" for="f-ancho">Ancho</label><input class="form-control" id="f-ancho" name="ancho" inputmode="decimal" placeholder="14"></div>
            <button class="btn btn-brand"><x-icon name="search" /> Buscar</button>
        </div>
    </form>
</section>

<section class="container value-strip" aria-label="Por qué comprar aquí">
    <ul>
        <li><x-icon name="ring" /> Rolineras marca SKF</li>
        <li><x-icon name="search" /> Búsqueda por código o medida</li>
        <li><x-icon name="chat" /> Pedido directo por WhatsApp</li>
        <li><x-icon name="pin" /> En Valle de la Pascua</li>
    </ul>
</section>

@if ($featured->isNotEmpty())
<section class="container page-section">
    <div class="section-head">
        <div>
            <h2 class="section-title">Productos destacados</h2>
            <p class="section-lead">Una selección de rolineras y piezas de nuestro catálogo.</p>
        </div>
        <a class="section-link" href="{{ route('catalog.index') }}">Ver todo el catálogo <x-icon name="arrow" /></a>
    </div>
    <div class="product-grid product-grid-wide">
        @foreach ($featured as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>
</section>
@endif

<section class="help-split">
    <div class="container help-split-inner">
        <img src="{{ asset('img/site/rolineras.webp') }}" alt="Rolinera cónica y rolineras de bolas" width="1400" height="834" loading="lazy">
        <div>
            <h2 class="section-title">¿No sabes tu código?</h2>
            <p>Mídela con un calibrador o escríbenos con el uso de la pieza. Nuestra guía te muestra qué medir y cómo leer el código grabado en la rolinera.</p>
            <div class="help-actions">
                <a class="btn btn-brand" href="{{ url('/guia') }}"><x-icon name="ruler" /> Ver guía de medidas</a>
                <a class="btn btn-outline-brand" href="{{ $whatsappUrl }}" rel="noopener"><x-icon name="chat" /> Consultar por WhatsApp</a>
            </div>
        </div>
    </div>
</section>

<section class="band band-navy">
    <div class="container">
        <h2 class="section-title">Cómo comprar</h2>
        <ol class="steps">
            <li><strong>Arma tu pedido</strong><span>Busca tus piezas y agrégalas a Mi pedido.</span></li>
            <li><strong>Envíalo por WhatsApp</strong><span>El mensaje se escribe solo; solo tienes que enviarlo.</span></li>
            <li><strong>Confirmamos</strong><span>Te respondemos con la disponibilidad y la forma de pago.</span></li>
        </ol>
    </div>
</section>
@endsection
