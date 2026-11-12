@extends('layouts.app')

@section('title', $category ? 'Editar categoría' : 'Crear categoría')

@section('content')
<section class="mx-auto max-w-3xl space-y-7 px-5 py-8 sm:px-8 sm:py-12">
    @include('admin._navigation')
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Administración · Categorías</p>
        <h1 class="mt-2 font-display text-3xl font-extrabold text-blue-950">{{ $category ? 'Editar categoría' : 'Crear categoría' }}</h1>
        <p class="mt-2 text-sm text-slate-600">El slug se generará automáticamente a partir del nombre.</p>
    </div>

    <form method="POST" action="{{ $category ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        @if ($category)
            @method('PUT')
        @endif
        <div>
            <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Nombre</label>
            <input id="name" name="name" value="{{ old('name', $category?->name) }}" required maxlength="255" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-700 focus:ring-blue-700">
            @error('name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="description" class="mb-2 block text-sm font-semibold text-slate-700">Descripción</label>
            <textarea id="description" name="description" rows="4" maxlength="2000" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-700 focus:ring-blue-700">{{ old('description', $category?->description) }}</textarea>
            @error('description') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="sort_order" class="mb-2 block text-sm font-semibold text-slate-700">Orden</label>
            <input id="sort_order" type="number" min="0" max="100000" name="sort_order" value="{{ old('sort_order', $category?->sort_order ?? 0) }}" required class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-700 focus:ring-blue-700">
            @error('sort_order') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div class="flex flex-wrap gap-3 pt-2">
            <button class="rounded-xl bg-blue-800 px-5 py-3 text-sm font-bold text-white hover:bg-blue-900">{{ $category ? 'Guardar cambios' : 'Crear categoría' }}</button>
            <a href="{{ route('admin.categories.index') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</a>
        </div>
    </form>
</section>
@endsection
