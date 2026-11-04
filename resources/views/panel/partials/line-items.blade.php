<h2 class="form-section-title">Piezas</h2>
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
            <button type="button" class="line-remove" data-remove-line aria-label="Quitar esta pieza">×</button>
        </div>
    @endforeach
</div>
<button type="button" class="btn btn-sm btn-outline-brand mt-1" data-add-line>Agregar otra pieza</button>
<script>
(function () {
    var box = document.querySelector('[data-line-items]');
    var next = box.querySelectorAll('[data-line-item]').length;

    document.querySelector('[data-add-line]').addEventListener('click', function () {
        var copy = box.querySelector('[data-line-item]').cloneNode(true);
        copy.querySelector('select').name = 'items[' + next + '][product_id]';
        copy.querySelector('select').value = '';
        copy.querySelector('input').name = 'items[' + next + '][quantity]';
        copy.querySelector('input').value = 1;
        next++;
        box.appendChild(copy);
        copy.querySelector('select').focus();
    });

    box.addEventListener('click', function (event) {
        if (!event.target.closest('[data-remove-line]')) { return; }
        var rows = box.querySelectorAll('[data-line-item]');
        var row = event.target.closest('[data-line-item]');
        if (rows.length > 1) {
            row.remove();
        } else {
            row.querySelector('select').value = '';
            row.querySelector('input').value = 1;
        }
    });
})();
</script>
