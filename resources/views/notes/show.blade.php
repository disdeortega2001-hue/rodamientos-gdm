<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="referrer" content="no-referrer">
    <title>Nota de entrega {{ $order->delivery_note_number }} | {{ $business }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="note-page">
    <main class="note-sheet">
        @if ($order->status === \App\Enums\OrderStatus::Cancelled)
            <p class="note-void" role="status">ANULADA</p>
        @endif
        <header class="note-head">
            <div>
                <p class="note-business">{{ $business }}</p>
                <p>{{ $address ?: $city }}</p>
                @if ($rif)<p>RIF {{ $rif }}</p>@endif
                <p>WhatsApp: {{ \App\Support\Phone::normalize($phone) }}</p>
            </div>
            <div class="note-id">
                <h1>Nota de entrega</h1>
                <p class="note-number">{{ $order->delivery_note_number }}</p>
                <p>Fecha: {{ $order->delivery_note_issued_at?->format('d/m/Y') }}</p>
                <p>Pedido: {{ $order->number }}</p>
            </div>
        </header>
        <section class="note-client">
            <p><strong>Cliente:</strong> {{ $order->customer?->name }}</p>
            <p><strong>Teléfono:</strong> {{ $order->customer?->phone }}</p>
            <p><strong>Pago:</strong> {{ $order->paymentStatus()->label() }}</p>
        </section>
        <div class="table-responsive">
            @include('notes.lines')
        </div>
        <p class="note-legal">Este documento no sustituye una factura fiscal.</p>
        <div class="note-actions">
            <a class="btn btn-accent" href="{{ url('/nota/'.$order->delivery_note_token.'/pdf') }}">Descargar PDF</a>
            <button type="button" class="btn btn-outline-brand" onclick="window.print()">Imprimir</button>
        </div>
    </main>
</body>
</html>
