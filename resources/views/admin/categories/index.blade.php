@extends('layouts.app')

@section('title', 'Administrar categorías')

@section('content')
<section class="mx-auto max-w-7xl space-y-7 px-5 py-8 sm:px-8 sm:py-12">
    @include('admin._navigation')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Administración</p>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-blue-950">Categorías</h1>
        </div>
        <a href="{{ route('admin.categories.create') }}" class="rounded-xl bg-blue-800 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-900">Crear categoría</a>
    </div>

    <form method="GET" action="{{ route('admin.categories.index') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-[1fr_12rem_auto]">
        <label class="sr-only" for="category_search">Buscar categoría</label>
        <input id="category_search" name="search" value="{{ $filters['search'] ?? '' }}" type="search" maxlength="120" placeholder="Buscar categoría" class="rounded-xl border-slate-300 text-sm focus:border-blue-700 focus:ring-blue-700">
        <label class="sr-only" for="category_active">Estado</label>
        <select id="category_active" name="active" class="rounded-xl border-slate-300 text-sm focus:border-blue-700 focus:ring-blue-700">
            <option value="">Todas</option>
            <option value="1" @selected(($filters['active'] ?? null) === true)>Activas</option>
            <option value="0" @selected(($filters['active'] ?? null) === false)>Inactivas</option>
        </select>
        <button class="rounded-xl bg-blue-800 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-900">Filtrar</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-4">Categoría</th><th class="px-5 py-4">Slug</th><th class="px-5 py-4">Orden</th><th class="px-5 py-4">Publicaciones</th><th class="px-5 py-4">Estado</th><th class="px-5 py-4">Acciones</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-800">{{ $category->name }}</p>
                                <p class="mt-1 max-w-md text-slate-500">{{ $category->description ?: 'Sin descripción' }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $category->slug }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $category->sort_order }}</td>
                            <td class="px-5 py-4 font-semibold text-slate-700">{{ $category->listings_count }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $category->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ $category->is_active ? 'Activa' : 'Inactiva' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 font-semibold text-blue-800 hover:bg-blue-50">Editar</a>
                                    <form method="POST" action="{{ route('admin.categories.status', $category) }}" onsubmit="return confirm('{{ $category->is_active ? '¿Desactivar esta categoría?' : '¿Activar esta categoría?' }}')">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $category->is_active ? '0' : '1' }}">
                                        <button class="rounded-lg border border-slate-200 px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-50">{{ $category->is_active ? 'Desactivar' : 'Activar' }}</button>
                                    </form>
                                    @if ($category->listings_count === 0)
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('¿Eliminar esta categoría? Esta acción no se puede deshacer.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded-lg border border-red-200 px-3 py-1.5 font-semibold text-red-700 hover:bg-red-50">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No se encontraron categorías.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $categories->links() }}
</section>
@endsection
