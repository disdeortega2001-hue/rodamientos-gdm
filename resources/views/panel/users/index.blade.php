@extends('layouts.panel')

@section('title', 'Usuarios')

@section('content')
<h1 class="h3">Usuarios</h1>
@foreach ($users as $user)
    <form method="post" action="{{ url('/panel/usuarios/'.$user->id) }}" class="panel-form user-row">
        @csrf @method('put')
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label" for="name-{{ $user->id }}">Nombre</label><input class="form-control" id="name-{{ $user->id }}" name="name" value="{{ $user->name }}" required></div>
            <div class="col-md-3"><label class="form-label" for="email-{{ $user->id }}">Correo</label><input class="form-control" id="email-{{ $user->id }}" type="email" name="email" value="{{ $user->email }}" required></div>
            <div class="col-md-2"><label class="form-label" for="role-{{ $user->id }}">Rol</label>
                <select class="form-select" id="role-{{ $user->id }}" name="role">
                    @foreach (\App\Enums\Role::cases() as $role)
                        <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label" for="password-{{ $user->id }}">Nueva contraseña</label><input class="form-control" id="password-{{ $user->id }}" type="password" name="password" autocomplete="new-password"></div>
            <div class="col-md-1"><div class="form-check"><input class="form-check-input" id="active-{{ $user->id }}" type="checkbox" name="is_active" value="1" @checked($user->is_active)><label class="form-check-label" for="active-{{ $user->id }}">Activo</label></div></div>
            <div class="col-md-1"><button class="btn btn-outline-brand w-100">Guardar</button></div>
        </div>
    </form>
@endforeach
<h2 class="h5 mt-4">Nuevo usuario</h2>
<form method="post" action="{{ url('/panel/usuarios') }}" class="panel-form">
    @csrf
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label" for="name">Nombre</label><input class="form-control" id="name" name="name" value="{{ old('name') }}" required></div>
        <div class="col-md-3"><label class="form-label" for="email">Correo</label><input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" required></div>
        <div class="col-md-2"><label class="form-label" for="role">Rol</label>
            <select class="form-select" id="role" name="role">
                <option value="seller">Vendedor</option>
                <option value="admin">Administrador</option>
            </select>
        </div>
        <div class="col-md-2"><label class="form-label" for="password">Contraseña</label><input class="form-control" id="password" type="password" name="password" minlength="10" autocomplete="new-password" required></div>
        <div class="col-md-2"><button class="btn btn-brand w-100">Crear</button></div>
    </div>
</form>
@endsection
