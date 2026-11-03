@extends('layouts.panel')

@section('title', 'Cotización '.$quote->number)

@section('content')
<nav><a href="{{ url('/panel/cotizaciones') }}">Volver a cotizaciones</a></nav>
<div class="panel-title-row mt-2">
    <h1 class="h3">Cotización {{ $quote->number }}</h1>
    <div class="order-actions">
        <a class="btn btn-outline-brand" href="{{ url('/cotizacion/'.$quote->public_token.'/pdf') }}">Descargar PDF</a>
        <a class="btn btn-outline-brand" href="{{ $whatsappUrl }}" rel="noopener">Enviar por WhatsApp</a>
        @if ($quote->order)
            <a class="btn btn-brand" href="{{ url('/panel/pedidos/'.$quote->order_id) }}">Ver pedido {{ $quote->order->number }}</a>
        @else
            <form method="post" action="{{ url('/panel/cotizaciones/'.$quote->id.'/convertir') }}">@csrf<button class="btn btn-brand">Convertir en pedido</button></form>
        @endif
    </div>
</div>
<div class="panel-card">
    <p class="mb-1"><strong>Cliente:</strong> <a href="{{ url('/panel/clientes/'.$quote->customer_id) }}">{{ $quote->customer->name }}</a> · {{ $quote->customer->phone }}</p>
    <p class="mb-1"><strong>Fecha:</strong> {{ $quote->created_at->format('d/m/Y') }}</p>
    <p class="mb-1"><strong>Enlace para el cliente:</strong> <a href="{{ $link }}">{{ $link }}</a></p>
    @if ($quote->note)<p class="mb-0"><strong>Nota:</strong> {{ $quote->note }}</p>@endif
</div>
@include('quotes.lines')
@endsection
