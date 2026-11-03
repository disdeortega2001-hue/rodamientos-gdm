@props(['product'])
@php($out = $product->availability === \App\Enums\Availability::Out)
<article class="product-card">
    <a class="product-image" href="{{ route('catalog.show', $product->slug) }}" tabindex="-1" aria-hidden="true">
        @if ($product->imageUrl)
            <img src="{{ $product->imageUrl }}" alt="" loading="lazy" width="320" height="320">
            @if ($product->imageIsReference)
                <span class="image-tag">Imagen referencial</span>
            @endif
        @else
            <span class="image-placeholder">{{ $product->code }}</span>
        @endif
    </a>
    <div class="product-body">
        <h3 class="product-code"><a href="{{ route('catalog.show', $product->slug) }}">{{ $product->code }}</a></h3>
        @if ($product->measures)
            <p class="product-measures">{{ $product->measures }}</p>
        @endif
        <p class="product-description">{{ $product->description }}</p>
        <div class="product-foot">
            <x-product-price :product="$product" />
            <x-availability :product="$product" />
        </div>
        <button type="button" class="btn btn-accent w-100" data-add-to-cart="{{ $product->id }}" @disabled($out)>
            @if ($out)
                Agotado
            @else
                <x-icon name="cart" /> Agregar
            @endif
        </button>
    </div>
</article>
