<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Instalar | Rodamientos GDM</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="panel-body">
    <main class="panel-main">
        <div class="login-card">
            <h1 class="h4">Instalar el portal</h1>
            <p>Escribe la contraseña que pusiste en <strong>ADMIN_PASSWORD</strong>. Esto prepara la base de datos y crea tu administrador.</p>
            @isset($error)
                <div class="alert alert-danger" role="alert">{{ $error }}</div>
            @endisset
            <form method="post" action="{{ url('/instalar') }}">
                <div class="mb-3">
                    <label class="form-label" for="clave">Contraseña</label>
                    <input class="form-control" id="clave" type="password" name="clave" autocomplete="current-password" required autofocus>
                </div>
                <button class="btn btn-brand w-100">Instalar</button>
            </form>
        </div>
    </main>
</body>
</html>
