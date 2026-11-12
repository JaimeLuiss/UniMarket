@extends('layouts.app')

@section('title', 'Marketplace')

@section('content')
<section class="mx-auto max-w-7xl px-5 py-9 sm:px-8 sm:py-12">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-blue-700">Hecho en comunidad</p>
            <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-blue-950 sm:text-4xl">Marketplace</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Encuentra productos, servicios e intercambios dentro de tu campus.</p>
        </div>
        <a href="{{ route('marketplace.create') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-800 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-900">+ Crear publicación</a>
    </div>

    <form method="GET" action="{{ route('marketplace.index') }}" class="mt-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <label for="search" class="sr-only">Buscar productos o descripciones</label>
        <div class="flex flex-col gap-3 sm:flex-row">
            <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="form-input flex-1" placeholder="¿Qué estás buscando?">
            <button type="submit" class="rounded-xl bg-blue-800 px-7 py-3 text-sm font-bold text-white transition hover:bg-blue-900">Buscar</button>
            <a href="{{ route('marketplace.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 hover:text-blue-800">Limpiar</a>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <select name="category" aria-label="Filtrar por categoría" class="form-input">
                <option value="">Todas las categorías</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="type" aria-label="Filtrar por tipo" class="form-input">
                <option value="">Venta e intercambio</option>
                <option value="{{ \App\Models\Listing::TYPE_SALE }}" @selected(($filters['type'] ?? '') === \App\Models\Listing::TYPE_SALE)>Venta</option>
                <option value="{{ \App\Models\Listing::TYPE_EXCHANGE }}" @selected(($filters['type'] ?? '') === \App\Models\Listing::TYPE_EXCHANGE)>Intercambio</option>
            </select>
            <select name="availability" aria-label="Filtrar por disponibilidad" class="form-input">
                <option value="">Toda disponibilidad</option>
                <option value="{{ \App\Models\Listing::AVAILABILITY_DAILY }}" @selected(($filters['availability'] ?? '') === \App\Models\Listing::AVAILABILITY_DAILY)>Diaria</option>
                <option value="{{ \App\Models\Listing::AVAILABILITY_UNIQUE }}" @selected(($filters['availability'] ?? '') === \App\Models\Listing::AVAILABILITY_UNIQUE)>Artículo único</option>
            </select>
            <select name="status" aria-label="Filtrar por estado" class="form-input">
                <option value="">Todos los estados</option>
                @foreach (\App\Models\Listing::STATUSES as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
            <select name="sort" aria-label="Ordenar publicaciones" class="form-input">
                <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Más recientes</option>
                <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Más antiguas</option>
                <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Menor precio</option>
                <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Mayor precio</option>
            </select>
        </div>
    </form>

    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="mt-8 flex items-center justify-between text-sm text-slate-500">
        <p>{{ $listings->total() }} {{ $listings->total() === 1 ? 'publicación' : 'publicaciones' }}</p>
        @if (request()->hasAny(['search', 'category', 'type', 'availability', 'status']))
            <p>Filtros aplicados</p>
        @endif
    </div>

    @if ($listings->isNotEmpty())
        <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($listings as $listing)
                <x-listing-card :listing="$listing" />
            @endforeach
        </div>
        <div class="mt-9">{{ $listings->links() }}</div>
    @else
        <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-blue-50 text-2xl text-blue-800" aria-hidden="true">⌕</div>
            <h2 class="mt-5 font-display text-xl font-bold text-blue-950">Todavía no hay resultados</h2>
            <p class="mt-2 text-sm text-slate-500">Prueba con otros términos o cambia los filtros de búsqueda.</p>
        </div>
    @endif
</section>
@endsection
