@extends('layouts.panel')

@section('title', 'Categorías')

@section('content')
<h1 class="h3">Categorías</h1>
<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Nombre</th><th>Orden</th><th class="text-end">Productos</th><th></th></tr></thead>
        <tbody>
        @foreach ($categories as $category)
            <tr>
                <td colspan="2">
                    <form method="post" action="{{ url('/panel/categorias/'.$category->id) }}" class="category-row">
                        @csrf @method('put')
                        <input class="form-control" name="name" value="{{ $category->name }}" aria-label="Nombre" required>
                        <input class="form-control category-position" type="number" name="position" min="0" value="{{ $category->position }}" aria-label="Orden" required>
                        <button class="btn btn-sm btn-outline-brand">Guardar</button>
                    </form>
                </td>
                <td class="text-end">{{ $category->products_count }}</td>
                <td class="text-end">
                    <form method="post" action="{{ url('/panel/categorias/'.$category->id) }}" onsubmit="return confirm('¿Borrar esta categoría?')">
                        @csrf @method('delete')
                        <button class="btn btn-sm btn-link text-danger">Borrar</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<h2 class="h5 mt-4">Nueva categoría</h2>
<form method="post" action="{{ url('/panel/categorias') }}" class="category-row panel-form">
    @csrf
    <input class="form-control" name="name" placeholder="Nombre" aria-label="Nombre" required>
    <input class="form-control category-position" type="number" name="position" min="0" value="{{ $categories->count() + 1 }}" aria-label="Orden" required>
    <button class="btn btn-brand">Crear</button>
</form>
@endsection
