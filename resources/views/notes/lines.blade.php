@php($total = $order->totalCents())
<table class="table align-middle note-table" style="width:100%">
    <thead><tr><th>Pieza</th><th>Medidas</th><th style="text-align:right">Cantidad</th><th style="text-align:right">Precio</th><th style="text-align:right">Subtotal</th></tr></thead>
    <tbody>
    @foreach ($order->items as $item)
        <tr>
            <td>{{ $item->code }}</td>
            <td>{{ $item->measures }}</td>
            <td style="text-align:right">{{ $item->quantity }}</td>
            <td style="text-align:right">{{ \App\Support\Money::usd((int) $item->unit_price_cents) }}</td>
            <td style="text-align:right">{{ \App\Support\Money::usd($item->quantity * (int) $item->unit_price_cents) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr><th colspan="4" style="text-align:right">Total</th><th style="text-align:right">{{ \App\Support\Money::usd((int) $total) }}</th></tr>
        @if ($order->usd_rate)
            <tr><th colspan="4" style="text-align:right">En bolívares, a la tasa de la venta</th><th style="text-align:right">{{ \App\Support\Money::bs((int) $total, (string) $order->usd_rate) }}</th></tr>
        @endif
    </tfoot>
</table>
