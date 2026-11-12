@extends('layouts.app')

@section('title', 'Mis publicaciones')

@section('content')
<section class="mx-auto max-w-7xl px-5 py-9 sm:px-8 sm:py-12">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-blue-700">Tu actividad</p>
            <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-blue-950 sm:text-4xl">Mis publicaciones</h1>
            <p class="mt-2 text-sm text-slate-500">Administra tus productos e intercambios publicados.</p>
        </div>
        <a href="{{ route('marketplace.create') }}" class="inline-flex rounded-xl bg-blue-800 px-5 py-3 text-sm font-bold text-white hover:bg-blue-900">+ Crear publicación</a>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    @if ($listings->isEmpty())
        <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <h2 class="font-display text-xl font-bold text-blue-950">Aún no tienes publicaciones</h2>
            <p class="mt-2 text-sm text-slate-500">Comparte algo con tu comunidad universitaria.</p>
            <a href="{{ route('marketplace.create') }}" class="mt-5 inline-flex rounded-xl bg-blue-800 px-5 py-3 text-sm font-bold text-white hover:bg-blue-900">Crear mi primera publicación</a>
        </div>
    @else
        <div class="mt-7 space-y-4">
            @foreach ($listings as $listing)
                @php($cover = $listing->images->sortBy('sort_order')->first())
                <article class="grid gap-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[180px_1fr] sm:p-5">
                    <a href="{{ route('marketplace.show', $listing) }}" class="block overflow-hidden rounded-xl bg-slate-100">
                        @if ($cover)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($cover->path) }}" alt="{{ $cover->alt_text ?: $listing->title }}" class="aspect-[4/3] size-full object-cover">
                        @else
                            <span class="flex aspect-[4/3] items-center justify-center text-xs font-semibold text-slate-400">Sin fotografía</span>
                        @endif
                    </a>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-blue-700">{{ $listing->category->name }}</p>
                                <a href="{{ route('marketplace.show', $listing) }}" class="mt-1 inline-block font-display text-xl font-bold text-blue-950 hover:text-blue-700">{{ $listing->title }}</a>
                            </div>
                            <p class="font-bold text-slate-900">
                                @if ($listing->listing_type === \App\Models\Listing::TYPE_SALE && $listing->price !== null)
                                    ${{ number_format((float) $listing->price, 2) }}
                                @else
                                    Intercambio
                                @endif
                            </p>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Publicado {{ $listing->published_at?->format('d/m/Y') ?? 'sin fecha' }}</p>

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <form method="POST" action="{{ route('marketplace.availability', $listing) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <label for="status-{{ $listing->id }}" class="sr-only">Estado de {{ $listing->title }}</label>
                                <select id="status-{{ $listing->id }}" name="status" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">
                                    @if ($listing->availability_type === \App\Models\Listing::AVAILABILITY_DAILY)
                                        <option value="{{ \App\Models\Listing::STATUS_AVAILABLE }}" @selected($listing->status === \App\Models\Listing::STATUS_AVAILABLE)>Disponible</option>
                                        <option value="{{ \App\Models\Listing::STATUS_SOLD_OUT_TODAY }}" @selected($listing->status === \App\Models\Listing::STATUS_SOLD_OUT_TODAY)>Agotado por hoy</option>
                                    @else
                                        <option value="{{ \App\Models\Listing::STATUS_AVAILABLE }}" @selected($listing->status === \App\Models\Listing::STATUS_AVAILABLE)>Disponible</option>
                                        <option value="{{ \App\Models\Listing::STATUS_RESERVED }}" @selected($listing->status === \App\Models\Listing::STATUS_RESERVED)>Reservado</option>
                                        <option value="{{ \App\Models\Listing::STATUS_SOLD }}" @selected($listing->status === \App\Models\Listing::STATUS_SOLD)>Vendido</option>
                                        <option value="{{ \App\Models\Listing::STATUS_EXCHANGED }}" @selected($listing->status === \App\Models\Listing::STATUS_EXCHANGED)>Intercambiado</option>
                                    @endif
                                </select>
                                @if ($listing->availability_type === \App\Models\Listing::AVAILABILITY_DAILY && $listing->stock !== null)
                                    <label for="stock-{{ $listing->id }}" class="sr-only">Cantidad disponible</label>
                                    <input id="stock-{{ $listing->id }}" name="stock" type="number" min="0" value="{{ $listing->stock }}" class="w-24 rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                @endif
                                <button type="submit" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-bold text-blue-800 hover:bg-blue-100">Actualizar</button>
                            </form>
                            <a href="{{ route('marketplace.edit', $listing) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:border-blue-200 hover:text-blue-800">Editar</a>
                            <form method="POST" action="{{ route('marketplace.destroy', $listing) }}" onsubmit="return confirm('¿Eliminar esta publicación y sus fotografías?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg border border-red-100 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-50">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-8">{{ $listings->links() }}</div>
    @endif
</section>
@endsection
