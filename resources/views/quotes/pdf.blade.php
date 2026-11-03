<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $quote->number }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #14284B; }
    h1 { font-size: 20px; margin: 0; }
    .muted { color: #4a5568; }
    .header { border-bottom: 3px solid #F08A1D; padding-bottom: 10px; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border-bottom: 1px solid #d5dbe5; padding: 6px; text-align: left; }
</style>
</head>
<body>
    <div class="header">
        <h1>{{ $business }}</h1>
        <div class="muted">{{ $city }}@if ($rif) · RIF {{ $rif }}@endif</div>
    </div>
    <h2>Cotización {{ $quote->number }}</h2>
    <p>Para: <strong>{{ $quote->customer->name }}</strong><br>Fecha: {{ $quote->created_at->format('d/m/Y') }}</p>
    @include('quotes.lines')
    <p class="muted">Precios sujetos a confirmación de disponibilidad.</p>
</body>
</html>
