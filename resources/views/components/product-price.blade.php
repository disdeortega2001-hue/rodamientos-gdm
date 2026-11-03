@props(['product'])
<div class="product-price">
    @if ($product->priceText)
        <strong>{{ $product->priceText }}</strong>
        @if ($product->bsText)
            <small>{{ $product->bsText }}</small>
        @endif
    @else
        <strong class="price-ask">Consultar precio</strong>
    @endif
</div>
