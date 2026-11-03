@php($total = $quote->totalCents())
<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Pieza</th><th>Medidas</th><th style="text-align:right">Cantidad</th><th style="text-align:right">Precio</th><th style="text-align:right">Subtotal</th></tr></thead>
        <tbody>
        @foreach ($quote->items as $item)
            <tr>
                <td>{{ $item->code }}</td>
                <td>{{ $item->measures }}</td>
                <td style="text-align:right">{{ $item->quantity }}</td>
                <td style="text-align:right">{{ $item->unit_price_cents === null ? 'Consultar' : \App\Support\Money::usd($item->unit_price_cents) }}</td>
                <td style="text-align:right">{{ $item->unit_price_cents === null ? 'Por confirmar' : \App\Support\Money::usd($item->quantity * $item->unit_price_cents) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr><th colspan="4" style="text-align:right">Total estimado</th><th style="text-align:right">{{ $total === null ? 'Por confirmar' : \App\Support\Money::usd($total) }}</th></tr>
            @if ($total !== null && $quote->usd_rate)
                <tr><th colspan="4" style="text-align:right">En bolívares, a la tasa de la cotización</th><th style="text-align:right">{{ \App\Support\Money::bs($total, (string) $quote->usd_rate) }}</th></tr>
            @endif
        </tfoot>
    </table>
</div>
