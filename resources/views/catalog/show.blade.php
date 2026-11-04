@extends('layouts.public')

@section('title', $product->code.' | Rodamiento GDM')
@section('description', $product->code.': '.$product->description.'. '.($product->measures ? 'Medidas '.$product->measures.'. ' : '').'Rodamiento GDM, Valle de la Pascua.')

@php($out = $product->availability === \App\Enums\Availability::Out)

@section('content')
<section class="container page-section">
    <nav class="breadcrumbs" aria-label="Ruta">
        <a href="{{ route('catalog.index') }}">Catálogo</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('catalog.index', ['categoria' => $product->categorySlug]) }}">{{ $product->category }}</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">{{ $product->code }}</span>
    </nav>
    <div class="product-detail">
        <div class="product-detail-image">
            @if ($product->imageUrl)
                <img src="{{ $product->imageUrl }}" alt="Foto de {{ $product->code }}" width="640" height="640">
                @if ($product->imageIsReference)
                    <span class="image-tag">Imagen referencial</span>
                @endif
            @else
                <span class="image-placeholder">{{ $product->code }}</span>
            @endif
        </div>
        <div class="product-detail-info">
            <h1 class="page-title">{{ $product->code }}</h1>
            <p class="detail-description">{{ $product->description }}</p>

            <div class="buy-box">
                <div class="buy-box-top">
                    <x-product-price :product="$product" />
                    <x-availability :product="$product" />
                </div>
                <div class="buy-row">
                    <div class="qty" data-qty>
                        <button type="button" class="qty-btn" data-qty-step="-1" aria-label="Quitar uno">−</button>
                        <input class="qty-input" data-qty-input type="number" min="1" max="999" value="1" aria-label="Cantidad" @disabled($out)>
                        <button type="button" class="qty-btn" data-qty-step="1" aria-label="Agregar uno">+</button>
                    </div>
                    <button type="button" class="btn btn-accent buy-btn" data-add-to-cart="{{ $product->id }}" data-qty-source @disabled($out)>
                        @if ($out)
                            Agotado
                        @else
                            <x-icon name="cart" /> Agregar al pedido
                        @endif
                    </button>
                </div>
                <a class="btn btn-outline-brand w-100" href="{{ $whatsappUrl }}" rel="noopener"><x-icon name="chat" /> Consultar por WhatsApp</a>
                <p class="buy-note">Confirmamos disponibilidad y forma de pago por WhatsApp.</p>
            </div>

            @if ($product->specs !== [])
                <h2 class="spec-title">Ficha técnica</h2>
                <dl class="spec-table">
                    @foreach ($product->specs as [$label, $value])
                        <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                    @endforeach
                    <div><dt>Categoría</dt><dd>{{ $product->category }}</dd></div>
                </dl>
            @endif
        </div>
    </div>

    @if ($related->isNotEmpty())
        <h2 class="section-title related-title">También te puede interesar</h2>
        <div class="product-grid product-grid-wide">
            @foreach ($related as $item)
                <x-product-card :product="$item" />
            @endforeach
        </div>
    @endif
</section>
@endsection
