@extends('layouts.panel')

@section('title', 'Acceso')

@section('content')
<div class="login-card">
    <x-logo class="brand-dark" />
    <h1 class="h4">Acceso del personal</h1>
    <form method="post" action="{{ url('/panel/acceso') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">Correo</label>
            <input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Contraseña</label>
            <input class="form-control" id="password" type="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-brand w-100">Entrar</button>
    </form>
</div>
@endsection
