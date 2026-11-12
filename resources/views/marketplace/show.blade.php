@extends('layouts.app')

@section('title', $listing->title)

@section('content')
@php
    $statusLabels = [
        \App\Models\Listing::STATUS_AVAILABLE => 'Disponible',
        \App\Models\Listing::STATUS_SOLD_OUT_TODAY => 'Agotado por hoy',
        \App\Models\Listing::STATUS_RESERVED => 'Reservado',
        \App\Models\Listing::STATUS_SOLD => 'Vendido',
        \App\Models\Listing::STATUS_EXCHANGED => 'Intercambiado',
    ];
@endphp
<section class="mx-auto max-w-7xl px-5 py-8 sm:px-8 sm:py-12">
    <a href="{{ route('marketplace.index') }}" class="text-sm font-bold text-blue-800 hover:text-blue-950">← Volver al marketplace</a>

    @if (session('status'))
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="mt-6 grid gap-9 lg:grid-cols-[1.15fr_0.85fr]">
        <div>
            @if ($listing->images->isNotEmpty())
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($listing->images as $image)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->path) }}" alt="{{ $image->alt_text ?: $listing->title }}" class="aspect-[4/3] w-full rounded-2xl bg-slate-100 object-cover {{ $loop->first && $loop->count > 1 ? 'sm:col-span-2 sm:aspect-[16/9]' : '' }}">
                    @endforeach
                </div>
            @else
                <div class="flex aspect-[4/3] items-center justify-center rounded-2xl bg-slate-100 text-sm font-semibold text-slate-400">Esta publicación no tiene fotografías</div>
            @endif
        </div>

        <div>
            <p class="text-sm font-bold uppercase tracking-[0.14em] text-blue-700">{{ $listing->category->name }}</p>
            <h1 class="mt-3 font-display text-3xl font-extrabold leading-tight tracking-tight text-blue-950 sm:text-4xl">{{ $listing->title }}</h1>
            <div class="mt-5 flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-800">{{ $statusLabels[$listing->status] ?? $listing->status }}</span>
                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700">{{ $listing->availability_type === \App\Models\Listing::AVAILABILITY_DAILY ? 'Disponible diariamente' : 'Artículo único' }}</span>
                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700">{{ $listing->listing_type === \App\Models\Listing::TYPE_SALE ? 'Venta' : 'Intercambio' }}</span>
            </div>

            @if ($listing->listing_type === \App\Models\Listing::TYPE_SALE && $listing->price !== null)
                <p class="mt-6 text-3xl font-extrabold text-slate-950">${{ number_format((float) $listing->price, 2) }}</p>
            @else
                <p class="mt-6 text-xl font-extrabold text-blue-800">Disponible para intercambio</p>
            @endif

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <span class="text-sm text-slate-500">{{ $listing->favorited_by_count }} {{ $listing->favorited_by_count === 1 ? 'favorito' : 'favoritos' }}</span>
                @auth
                    @if (auth()->id() !== $listing->user_id && (($listing->is_favorited ?? false) || $listing->isAvailable()))
                        <form method="POST" action="{{ route(($listing->is_favorited ?? false) ? 'marketplace.unfavorite' : 'marketplace.favorite', $listing) }}">
                            @csrf
                            @if ($listing->is_favorited ?? false)
                                @method('DELETE')
                            @endif
                            <button type="submit" class="rounded-xl border border-blue-100 px-4 py-2 text-sm font-bold text-blue-800 transition hover:bg-blue-50">
                                {{ ($listing->is_favorited ?? false) ? 'Quitar de favoritos' : 'Guardar en favoritos' }}
                            </button>
                        </form>
                    @endif
                @endauth
            </div>

            <div class="mt-8 border-y border-slate-200 py-6">
                <h2 class="font-display text-lg font-bold text-blue-950">Descripción</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $listing->description }}</p>
            </div>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                @if ($listing->condition)
                    <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Condición</dt>
                        <dd class="mt-1 font-bold capitalize text-slate-800">{{ $listing->condition === \App\Models\Listing::CONDITION_NEW ? 'Nuevo' : 'Usado' }}</dd>
                    </div>
                @endif
                @if ($listing->availability_type === \App\Models\Listing::AVAILABILITY_DAILY && $listing->stock !== null)
                    <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cantidad disponible</dt>
                        <dd class="mt-1 font-bold text-slate-800">{{ $listing->stock }}</dd>
                    </div>
                @endif
                <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Entrega en campus</dt>
                    <dd class="mt-1 font-bold text-slate-800">{{ $listing->campus_location ?: 'A coordinar en el campus' }}</dd>
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Publicado</dt>
                    <dd class="mt-1 font-bold text-slate-800">{{ $listing->published_at?->format('d/m/Y') }}</dd>
                </div>
            </dl>

            <section class="mt-7 rounded-2xl border border-slate-200 bg-white p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Vendedor</p>
                <h2 class="mt-2 font-display text-lg font-bold text-blue-950">{{ $listing->user->name }}</h2>
                @if ($listing->entrepreneurProfile)
                    <a href="{{ route('entrepreneurs.show', $listing->entrepreneurProfile->slug) }}" class="mt-1 inline-block text-sm font-semibold text-blue-800 hover:text-blue-950">{{ $listing->entrepreneurProfile->business_name }}</a>
                    @if ($listing->entrepreneurProfile->campus_location)
                        <p class="mt-2 text-sm text-slate-500">{{ $listing->entrepreneurProfile->campus_location }}</p>
                    @endif
                @else
                    <p class="mt-1 text-sm text-slate-500">Estudiante de la comunidad UniMarket</p>
                @endif
            </section>

            @can('update', $listing)
                <a href="{{ route('marketplace.edit', $listing) }}" class="mt-5 inline-flex rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:border-blue-200 hover:text-blue-800">Editar publicación</a>
            @endcan
        </div>
    </div>

    @if ($otherListings->isNotEmpty())
        <section class="mt-14">
            <h2 class="font-display text-2xl font-extrabold text-blue-950">Más publicaciones de {{ $listing->user->name }}</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($otherListings as $otherListing)
                    <x-listing-card :listing="$otherListing" />
                @endforeach
            </div>
        </section>
    @endif
</section>
@endsection
