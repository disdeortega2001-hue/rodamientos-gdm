@extends('layouts.panel')

@section('title', $product->exists ? 'Editar '.$product->code : 'Nuevo producto')

@section('content')
<h1 class="h3">{{ $product->exists ? 'Editar '.$product->code : 'Nuevo producto' }}</h1>
<form method="post" action="{{ $product->exists ? url('/panel/productos/'.$product->id) : url('/panel/productos') }}" class="panel-form">
    @csrf
    @if ($product->exists) @method('put') @endif
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="code">Código</label>
            <input class="form-control" id="code" name="code" value="{{ old('code', $product->code) }}" required maxlength="100">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="category_id">Categoría</label>
            <select class="form-select" id="category_id" name="category_id" required>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="measures">Medidas</label>
            <input class="form-control" id="measures" name="measures" value="{{ old('measures', $product->measures) }}" maxlength="255">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="brand">Marca</label>
            <input class="form-control" id="brand" name="brand" value="{{ old('brand', $product->brand) }}" maxlength="100">
        </div>
        <div class="col-12">
            <label class="form-label" for="description">Descripción</label>
            <textarea class="form-control" id="description" name="description" rows="2" required maxlength="500">{{ old('description', $product->description) }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="price">Precio de venta en dólares</label>
            <input class="form-control" id="price" name="price" inputmode="decimal" value="{{ old('price', \App\Support\Money::input($product->price_cents)) }}">
            <div class="form-text">Déjalo vacío para mostrar "Consultar precio".</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="low_stock_threshold">Aviso de pocas unidades</label>
            <input class="form-control" id="low_stock_threshold" name="low_stock_threshold" type="number" min="0" value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 3) }}" required>
        </div>
        <div class="col-12 panel-checks">
            <div class="form-check"><input class="form-check-input" id="track_stock" type="checkbox" name="track_stock" value="1" @checked(old('track_stock', $product->track_stock ?? true))><label class="form-check-label" for="track_stock">Controlar existencias</label></div>
            <div class="form-check"><input class="form-check-input" id="is_visible" type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $product->is_visible ?? true))><label class="form-check-label" for="is_visible">Visible en el catálogo</label></div>
            <div class="form-check"><input class="form-check-input" id="is_featured" type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))><label class="form-check-label" for="is_featured">Destacado en el inicio</label></div>
        </div>
    </div>
    <button class="btn btn-brand mt-3">Guardar</button>
</form>

@if ($product->exists)
    <form method="post" action="{{ url('/panel/productos/'.$product->id) }}" class="mt-2" onsubmit="return confirm('¿Borrar este producto? Si tiene historial, solo se ocultará.')">
        @csrf @method('delete')
        <button class="btn btn-link text-danger p-0">Borrar producto</button>
    </form>

    <h2 class="h5 mt-4">Fotos</h2>
    <div class="panel-images">
        @foreach ($product->images as $image)
            <figure>
                <img src="{{ $image->url() }}" alt="Foto de {{ $product->code }}" width="120" height="120">
                <figcaption>
                    @if ($image->is_reference)<div>Imagen referencial</div>@endif
                    @if ($image->credit_author)<div>{{ $image->credit_author }}</div>@endif
                    <form method="post" action="{{ url('/panel/imagenes/'.$image->id) }}" onsubmit="return confirm('¿Borrar esta foto?')">
                        @csrf @method('delete')
                        <button class="btn btn-link btn-sm text-danger p-0">Borrar</button>
                    </form>
                </figcaption>
            </figure>
        @endforeach
    </div>
    <form method="post" action="{{ url('/panel/productos/'.$product->id.'/imagenes') }}" enctype="multipart/form-data" class="panel-form">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="image">Agregar foto (JPG, PNG o WebP, hasta 4 MB)</label>
                <input class="form-control" id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required>
            </div>
            <div class="col-md-6 align-self-end">
                <div class="form-check"><input class="form-check-input" id="is_reference" type="checkbox" name="is_reference" value="1"><label class="form-check-label" for="is_reference">Es una imagen referencial</label></div>
            </div>
            <div class="col-md-4"><label class="form-label" for="credit_author">Autor (si es de internet)</label><input class="form-control" id="credit_author" name="credit_author"></div>
            <div class="col-md-4"><label class="form-label" for="credit_license">Licencia</label><input class="form-control" id="credit_license" name="credit_license"></div>
            <div class="col-md-4"><label class="form-label" for="credit_url">Enlace de origen</label><input class="form-control" id="credit_url" name="credit_url" type="url"></div>
        </div>
        <button class="btn btn-outline-brand mt-3">Subir foto</button>
    </form>
@endif
@endsection
