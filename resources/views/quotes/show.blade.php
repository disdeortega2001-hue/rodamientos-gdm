@extends('layouts.public')

@section('title', 'Cotización '.$quote->number.' | Rodamientos GDM')

@section('content')
<section class="container page-section narrow">
    <h1 class="page-title">Cotización {{ $quote->number }}</h1>
    <p>Para: <strong>{{ $quote->customer->name }}</strong> · {{ $quote->created_at->format('d/m/Y') }}</p>
    @include('quotes.lines')
    <p class="cart-note">Precios sujetos a confirmación de disponibilidad.</p>
    <a class="btn btn-accent" href="{{ url('/cotizacion/'.$quote->public_token.'/pdf') }}">Descargar PDF</a>
</section>
@endsection
