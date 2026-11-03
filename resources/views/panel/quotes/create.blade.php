@extends('layouts.panel')

@section('title', 'Nueva cotización')

@section('content')
<h1 class="h3">Nueva cotización</h1>
<form method="post" action="{{ url('/panel/cotizaciones') }}" class="panel-form">
    @csrf
    <input type="hidden" name="form_token" value="{{ \Illuminate\Support\Str::uuid() }}">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="name">Cliente</label><input class="form-control" id="name" name="name" value="{{ old('name') }}" required></div>
        <div class="col-md-6"><label class="form-label" for="phone">Teléfono</label><input class="form-control" id="phone" name="phone" type="tel" value="{{ old('phone') }}" required></div>
    </div>
    @include('panel.partials.line-items')
    <div class="mt-3"><label class="form-label" for="note">Nota</label><textarea class="form-control" id="note" name="note" rows="2">{{ old('note') }}</textarea></div>
    <button class="btn btn-brand mt-3">Crear cotización</button>
</form>
@endsection
