@extends('layouts.public')

@section('title', 'Catálogo de rolineras SKF | Rodamientos GDM')
@section('description', 'Catálogo de rolineras y rodamientos SKF, crucetas y estoperas. Busca por código o medida en Rodamientos GDM, Valle de la Pascua.')

@section('content')
<section class="page-band">
    <div class="container">
        <h1 class="page-title">Catálogo</h1>
        <nav class="chips" aria-label="Categorías">
            <a class="chip" href="{{ route('catalog.index', $keep) }}" @if ($categorySlug === '') aria-current="true" @endif>Todas</a>
            @foreach ($categories as $category)
                <a class="chip" href="{{ route('catalog.index', ['categoria' => $category->slug] + $keep) }}" @if ($categorySlug === $category->slug) aria-current="true" @endif>{{ $category->name }}</a>
            @endforeach
        </nav>
    </div>
</section>

<section class="container catalog-layout">
    <form class="catalog-filters" method="get" action="{{ route('catalog.index') }}">
        @if ($categorySlug !== '')<input type="hidden" name="categoria" value="{{ $categorySlug }}">@endif
        <div class="filter-block">
            <label class="form-label" for="q">Código o descripción</label>
            <input class="form-control" id="q" type="search" name="q" value="{{ $search }}" placeholder="Ej.: 6204 o 20x47">
        </div>
        <fieldset class="filter-block">
            <legend class="form-label">Medidas en mm</legend>
            <div class="filter-measures">
                <div><label class="filter-mini" for="interior">Interior</label><input class="form-control" id="interior" name="interior" inputmode="decimal" value="{{ $wanted['interior'] }}"></div>
                <div><label class="filter-mini" for="exterior">Exterior</label><input class="form-control" id="exterior" name="exterior" inputmode="decimal" value="{{ $wanted['exterior'] }}"></div>
                <div><label class="filter-mini" for="ancho">Ancho</label><input class="form-control" id="ancho" name="ancho" inputmode="decimal" value="{{ $wanted['ancho'] }}"></div>
            </div>
        </fieldset>
        <div class="filter-block">
            <label class="form-label" for="orden">Ordenar por</label>
            <select class="form-select" id="orden" name="orden">
                <option value="">Código</option>
                <option value="precio_asc" @selected($sort === 'precio_asc')>Menor precio</option>
                <option value="precio_desc" @selected($sort === 'precio_desc')>Mayor precio</option>
            </select>
        </div>
        <div class="form-check filter-check">
            <input class="form-check-input" id="disponibles" type="checkbox" name="disponibles" value="1" @checked(request()->boolean('disponibles'))>
            <label class="form-check-label" for="disponibles">Solo disponibles</label>
        </div>
        <button class="btn btn-brand w-100"><x-icon name="search" /> Buscar</button>
        @if ($keep !== [] || $categorySlug !== '')
            <a class="filter-clear" href="{{ route('catalog.index') }}">Limpiar filtros</a>
        @endif
    </form>

    <div class="catalog-results">
        <p class="result-count">{{ $products->count() }} {{ $products->count() === 1 ? 'producto' : 'productos' }}</p>
        @if ($products->isEmpty())
            <div class="empty-state">
                <h2>No encontramos esa pieza en el catálogo</h2>
                <p>Escríbenos y te ayudamos a ubicarla por código, medida o aplicación.</p>
                <a class="btn btn-accent" href="{{ $whatsappSearchUrl }}" rel="noopener"><x-icon name="chat" /> Consultar por WhatsApp</a>
            </div>
        @else
            <div class="product-grid">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
