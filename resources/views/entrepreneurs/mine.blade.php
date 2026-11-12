@extends('layouts.app')

@section('title', 'Mi emprendimiento')

@section('content')
<section class="mx-auto max-w-7xl px-5 py-9 sm:px-8 sm:py-12">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-blue-700">Tu negocio en el campus</p>
            <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-blue-950 sm:text-4xl">Mi emprendimiento</h1>
        </div>
        <a href="{{ route('my-entrepreneur.edit') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:border-blue-200 hover:text-blue-800">Editar perfil</a>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    <article class="mt-7 grid gap-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-[120px_1fr] sm:p-8">
        @if ($profile->logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($profile->logo_path) }}" alt="Logotipo de {{ $profile->business_name }}" class="size-28 rounded-2xl border border-slate-200 object-cover">
        @else
            <div class="flex size-28 items-center justify-center rounded-2xl bg-blue-50 font-display text-3xl font-extrabold text-blue-800">{{ str($profile->business_name)->substr(0, 1)->upper() }}</div>
        @endif
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="font-display text-2xl font-extrabold text-blue-950">{{ $profile->business_name }}</h2>
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $profile->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $profile->is_active ? 'Activo' : 'Inactivo' }}</span>
            </div>
            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $profile->description }}</p>
            <p class="mt-3 text-sm text-slate-500">{{ $profile->campus_location ?: 'Sin ubicación en campus' }} · {{ $profile->followers()->count() }} seguidores</p>
            @if ($profile->is_active)
                <a href="{{ route('entrepreneurs.show', $profile->slug) }}" class="mt-4 inline-block text-sm font-bold text-blue-800 hover:text-blue-950">Ver página pública →</a>
            @endif
            <form method="POST" action="{{ route('my-entrepreneur.status') }}" class="mt-4">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_active" value="{{ $profile->is_active ? 0 : 1 }}">
                <button type="submit" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:border-blue-200 hover:text-blue-800">{{ $profile->is_active ? 'Desactivar emprendimiento' : 'Activar emprendimiento' }}</button>
            </form>
        </div>
    </article>

    <div class="mt-10">
        <h2 class="font-display text-2xl font-extrabold text-blue-950">Publicaciones asociadas</h2>
        @if ($listings->isEmpty())
            <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
                <p class="font-bold text-blue-950">Aún no tienes productos asociados</p>
                <p class="mt-2 text-sm text-slate-500">Al crear una publicación, selecciona este emprendimiento en el formulario.</p>
                <a href="{{ route('marketplace.create') }}" class="mt-5 inline-flex rounded-xl bg-blue-800 px-5 py-3 text-sm font-bold text-white hover:bg-blue-900">Crear publicación</a>
            </div>
        @else
            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($listings as $listing)
                    <x-listing-card :listing="$listing" />
                @endforeach
            </div>
            <div class="mt-8">{{ $listings->links() }}</div>
        @endif
    </div>
</section>
@endsection
