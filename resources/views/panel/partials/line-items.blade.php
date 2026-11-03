<h2 class="h5 mt-3">Piezas</h2>
<div data-line-items>
    @php($lines = old('items', [['product_id' => '', 'quantity' => 1]]))
    @foreach ($lines as $i => $line)
        <div class="line-item" data-line-item>
            <select class="form-select" name="items[{{ $i }}][product_id]" aria-label="Producto">
                <option value="">Elige un producto</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected((int) ($line['product_id'] ?? 0) === $product->id)>{{ $product->code }}{{ $product->measures ? ' · '.$product->measures : '' }}</option>
                @endforeach
            </select>
            <input class="form-control line-qty" type="number" min="1" max="999" name="items[{{ $i }}][quantity]" value="{{ $line['quantity'] ?? 1 }}" aria-label="Cantidad">
        </div>
    @endforeach
</div>
<button type="button" class="btn btn-sm btn-outline-brand mt-2" data-add-line>Agregar otra pieza</button>
<script>
document.querySelector('[data-add-line]').addEventListener('click', function () {
    var box = document.querySelector('[data-line-items]');
    var rows = box.querySelectorAll('[data-line-item]');
    var copy = rows[0].cloneNode(true);
    copy.querySelector('select').name = 'items[' + rows.length + '][product_id]';
    copy.querySelector('select').value = '';
    copy.querySelector('input').name = 'items[' + rows.length + '][quantity]';
    copy.querySelector('input').value = 1;
    box.appendChild(copy);
});
</script>
