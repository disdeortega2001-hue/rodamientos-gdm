<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $order->delivery_note_number }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #14284B; }
    p { margin: 2px 0; }
    .head { width: 100%; border-bottom: 3px solid #F08A1D; padding-bottom: 8px; margin-bottom: 12px; }
    .head td { vertical-align: top; border: 0; padding: 0; }
    .business { font-size: 18px; font-weight: bold; }
    .right { text-align: right; }
    .number { font-size: 16px; font-weight: bold; }
    .void { color: #a12a2a; font-size: 18px; font-weight: bold; text-align: center; border: 2px solid #a12a2a; padding: 4px; margin-bottom: 10px; }
    .note-table { border-collapse: collapse; margin-top: 12px; }
    .note-table th, .note-table td { border-bottom: 1px solid #d5dbe5; padding: 6px; text-align: left; }
    .legal { margin-top: 18px; color: #4d5a70; font-size: 10px; }
</style>
</head>
<body>
    @if ($order->status === \App\Enums\OrderStatus::Cancelled)
        <p class="void">ANULADA</p>
    @endif
    <table class="head"><tr>
        <td>
            <p class="business">{{ $business }}</p>
            <p>{{ $address ?: $city }}</p>
            @if ($rif)<p>RIF {{ $rif }}</p>@endif
            <p>WhatsApp: {{ \App\Support\Phone::normalize($phone) }}</p>
        </td>
        <td class="right">
            <p><strong>Nota de entrega</strong></p>
            @if ($order->delivery_note_number)<p class="number">{{ $order->delivery_note_number }}</p>@endif
            <p>Fecha: {{ $order->delivery_note_issued_at?->format('d/m/Y') }}</p>
            @if ($order->number)<p>Pedido: {{ $order->number }}</p>@endif
        </td>
    </tr></table>
    <p><strong>Cliente:</strong> {{ $order->customer?->name }}</p>
    @if ($order->customer?->document)
        <p><strong>Cédula / RIF:</strong> {{ $order->customer->document }}</p>
    @endif
    <p><strong>Teléfono:</strong> {{ $order->customer?->phone }}</p>
    @if ($order->customer?->address)
        <p><strong>Dirección:</strong> {{ $order->customer->address }}</p>
    @endif
    <p><strong>Pago:</strong> {{ $order->paymentStatus()->label() }}</p>
    @include('notes.lines')
    <p class="legal">Este documento no sustituye una factura fiscal.</p>
</body>
</html>
