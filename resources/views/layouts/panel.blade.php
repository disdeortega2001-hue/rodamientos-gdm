<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Panel') | Rodamientos GDM</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
@php
    $sections = [
        ['panel', 'Inicio', 'home', false],
        ['panel/pedidos', 'Pedidos', 'cart', false],
        ['panel/notas', 'Notas de entrega', 'check', false],
        ['panel/cotizaciones', 'Cotizaciones', 'file', false],
        ['panel/clientes', 'Clientes', 'users', false],
        ['panel/productos', 'Productos', 'ring', false],
        ['panel/inventario', 'Inventario', 'box', false],
        ['panel/categorias', 'Categorías', 'layers', true],
        ['panel/reportes', 'Finanzas', 'chart', true],
        ['panel/usuarios', 'Usuarios', 'users', true],
        ['panel/ajustes', 'Ajustes', 'gear', true],
    ];
@endphp
<body class="panel-body @auth panel-with-nav @endauth">
    @auth
    <header class="panel-header">
        <x-logo />
        <nav class="panel-nav" aria-label="Panel">
            @foreach ($sections as [$path, $label, $icon, $adminOnly])
                @if (! $adminOnly || auth()->user()->can('manage-all'))
                    <a href="{{ url($path) }}" @if (request()->is($path) || ($path !== 'panel' && request()->is($path.'/*'))) aria-current="page" @endif><x-icon :name="$icon" /> {{ $label }}</a>
                @endif
            @endforeach
        </nav>
        <form method="post" action="{{ url('/panel/salir') }}" class="panel-logout">
            @csrf
            <button class="btn btn-sm btn-outline-light">Cerrar sesión</button>
        </form>
    </header>
    @endauth
    <main class="panel-main">
        <div class="container-fluid panel-content">
            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
            @yield('content')
        </div>
    </main>
    <script src="{{ asset('js/panel.js') }}" defer></script>
</body>
</html>
