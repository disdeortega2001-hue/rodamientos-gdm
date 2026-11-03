@extends('layouts.panel')

@section('title', 'Importar Excel')

@section('content')
<h1 class="h3">Importar Excel inicial</h1>
@if ($imported)
    <div class="panel-card">
        <p class="mb-0">La carga inicial ya se hizo. Desde ahora, los cambios se registran en Inventario y Productos.</p>
    </div>
@else
    <form method="post" action="{{ url('/panel/importar') }}" enctype="multipart/form-data" class="panel-form">
        @csrf
        <p>Carga precios, costos, existencias y ventas previas de tu archivo de cotización. Solo se puede hacer una vez.</p>
        <div class="mb-3">
            <label class="form-label" for="archivo">Archivo .xlsx (hasta 5 MB)</label>
            <input class="form-control" id="archivo" type="file" name="archivo" accept=".xlsx" required>
        </div>
        <button class="btn btn-brand">Importar</button>
    </form>
@endif
@endsection
