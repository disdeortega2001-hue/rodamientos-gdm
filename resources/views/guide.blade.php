@extends('layouts.public')

@section('title', 'Guía de medidas y códigos de rolineras | Rodamientos GDM')
@section('description', 'Aprende a medir una rolinera y a leer su código: diámetro interior, diámetro exterior, ancho y el significado de 2RS.')

@section('content')
<section class="page-band">
    <div class="container">
        <h1 class="page-title">Guía de medidas y códigos</h1>
        <p class="page-band-lead">Cómo identificar tu rolinera en dos pasos, aunque el código ya no se lea.</p>
    </div>
</section>

<section class="container page-section guide-grid">
    <div>
        <h2 class="section-title">1. Mide la pieza</h2>
        <p>Usa un calibrador y anota las tres medidas en milímetros, siempre en este orden:</p>
        <ol class="guide-list">
            <li><strong>Diámetro interior:</strong> el hueco por donde pasa el eje.</li>
            <li><strong>Diámetro exterior:</strong> el borde externo de la rolinera.</li>
            <li><strong>Ancho:</strong> el grosor de la pieza.</li>
        </ol>
        <p>Con esas tres medidas puedes usar el <a href="{{ route('catalog.index') }}">buscador del catálogo</a>.</p>
    </div>
    <figure class="guide-figure">
        <svg viewBox="0 0 420 250" role="img" aria-labelledby="diagram-title">
            <title id="diagram-title">Diagrama de una rolinera con su diámetro interior, diámetro exterior y ancho</title>
            <circle cx="125" cy="115" r="92" fill="#f3f4f6" stroke="#14284b" stroke-width="3"/>
            <circle cx="125" cy="115" r="72" fill="none" stroke="#14284b" stroke-width="1.5"/>
            <circle cx="125" cy="115" r="50" fill="#f3f4f6" stroke="#14284b" stroke-width="1.5"/>
            <circle cx="125" cy="115" r="36" fill="#fff" stroke="#14284b" stroke-width="3"/>
            <g fill="#2b4570">
                <circle cx="125" cy="54" r="9"/><circle cx="125" cy="176" r="9"/><circle cx="64" cy="115" r="9"/><circle cx="186" cy="115" r="9"/>
                <circle cx="82" cy="72" r="9"/><circle cx="168" cy="72" r="9"/><circle cx="82" cy="158" r="9"/><circle cx="168" cy="158" r="9"/>
            </g>
            <line x1="89" y1="115" x2="161" y2="115" stroke="#d97706" stroke-width="2.5"/>
            <text x="125" y="108" text-anchor="middle" font-size="13" font-weight="600" fill="#14284b">Interior</text>
            <line x1="33" y1="228" x2="217" y2="228" stroke="#d97706" stroke-width="2.5"/>
            <line x1="33" y1="120" x2="33" y2="234" stroke="#d97706" stroke-dasharray="3 4"/>
            <line x1="217" y1="120" x2="217" y2="234" stroke="#d97706" stroke-dasharray="3 4"/>
            <text x="125" y="246" text-anchor="middle" font-size="13" font-weight="600" fill="#14284b">Exterior</text>
            <rect x="300" y="23" width="62" height="184" fill="#f3f4f6" stroke="#14284b" stroke-width="3"/>
            <line x1="300" y1="68" x2="362" y2="68" stroke="#14284b" stroke-width="1.5"/>
            <line x1="300" y1="162" x2="362" y2="162" stroke="#14284b" stroke-width="1.5"/>
            <line x1="300" y1="228" x2="362" y2="228" stroke="#d97706" stroke-width="2.5"/>
            <text x="331" y="246" text-anchor="middle" font-size="13" font-weight="600" fill="#14284b">Ancho</text>
        </svg>
    </figure>
</section>

<section class="band">
    <div class="container">
        <h2 class="section-title">2. Lee el código</h2>
        <p class="section-lead">El código suele estar grabado en el borde de la rolinera. Estos son los que manejamos con más frecuencia en el catálogo:</p>
        <div class="table-responsive guide-table">
            <table class="table align-middle">
                <thead><tr><th>En el código</th><th>Qué significa</th><th>Ejemplo</th></tr></thead>
                <tbody>
                    <tr><td><strong>62 / 63</strong></td><td>Rolinera rígida de bolas. La serie 63 es más reforzada que la 62.</td><td>6204, 6306</td></tr>
                    <tr><td><strong>Últimos dos dígitos</strong></td><td>Desde 04 en adelante, multiplícalos por 5 para saber el diámetro interior en milímetros.</td><td>6204: 04 × 5 = 20 mm</td></tr>
                    <tr><td><strong>2RS</strong></td><td>Lleva sellos de goma en ambos lados, que protegen del polvo y la humedad.</td><td>6205 2RS</td></tr>
                    <tr><td><strong>3 y 32</strong></td><td>Rolinera de rodillos cónicos, formada por un cono y una copa.</td><td>30204, 32210</td></tr>
                    <tr><td><strong>UC</strong></td><td>Rolinera de inserción para montar en chumacera.</td><td>UC205, UC207</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="container page-section help-block">
    <h2 class="section-title">¿Sigues con dudas?</h2>
    <p>Envíanos las medidas o una foto de la pieza y te ayudamos a identificarla.</p>
    <a class="btn btn-accent" href="{{ $whatsappUrl }}" rel="noopener"><x-icon name="chat" /> Consultar por WhatsApp</a>
</section>
@endsection
