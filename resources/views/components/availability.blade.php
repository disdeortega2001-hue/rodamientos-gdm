@props(['product'])
<span class="availability availability-{{ $product->availability->value }}">{{ $product->availability->label() }}</span>
