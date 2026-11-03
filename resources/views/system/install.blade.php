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
            <p>Crea tu usuario administrador. Esta página se cierra sola al terminar.</p>
            @isset($error)
                <div class="alert alert-danger" role="alert">{{ $error }}</div>
            @endisset
            <form method="post" action="{{ url('/instalar') }}">
                <div class="mb-3">
                    <label class="form-label" for="codigo">Código de instalación</label>
                    <input class="form-control" id="codigo" name="codigo" autocomplete="off" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Tu correo</label>
                    <input class="form-control" id="email" type="email" name="email" value="{{ $email ?? '' }}" autocomplete="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Contraseña (mínimo 10 caracteres)</label>
                    <input class="form-control" id="password" type="password" name="password" minlength="10" autocomplete="new-password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Repite la contraseña</label>
                    <input class="form-control" id="password_confirmation" type="password" name="password_confirmation" minlength="10" autocomplete="new-password" required>
                </div>
                <button class="btn btn-brand w-100">Instalar</button>
            </form>
        </div>
    </main>
</body>
</html>
