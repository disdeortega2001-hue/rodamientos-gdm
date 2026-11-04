@extends('layouts.public')

@section('title', 'Empresa y contacto | Rodamientos GDM')
@section('description', 'Rodamientos GDM vende rolineras SKF en Valle de la Pascua, Guárico. Contáctanos por WhatsApp.')

@section('content')
<section class="container page-section narrow">
    <h1 class="page-title">Empresa y contacto</h1>
    <p>Rodamientos GDM vende rolineras SKF, crucetas y estoperas en {{ $city }}, para automoción, maquinaria e industria.</p>
    <dl class="contact-list">
        <dt>WhatsApp</dt><dd><a href="{{ $whatsappUrl }}" rel="noopener">{{ \App\Support\Phone::normalize($phone) }}</a></dd>
        <dt>Instagram</dt><dd><a href="https://www.instagram.com/{{ $instagram }}/" target="_blank" rel="noopener noreferrer">{{ '@'.$instagram }}</a></dd>
        <dt>Ciudad</dt><dd>{{ $city }}</dd>
        @if ($address)<dt>Dirección</dt><dd>{{ $address }}</dd>@endif
        @if ($hours)<dt>Horario</dt><dd>{{ $hours }}</dd>@endif
        @if ($paymentMethods)<dt>Formas de pago</dt><dd>{{ $paymentMethods }}</dd>@endif
        @if ($rif)<dt>RIF</dt><dd>{{ $rif }}</dd>@endif
    </dl>
    <a class="btn btn-accent" href="{{ $whatsappUrl }}" rel="noopener">Escribir por WhatsApp</a>
</section>
@endsection
