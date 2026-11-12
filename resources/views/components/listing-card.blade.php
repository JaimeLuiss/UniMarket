@php
    $cover = $listing->images->sortBy('sort_order')->first();
    $statusLabels = [
        \App\Models\Listing::STATUS_AVAILABLE => 'Disponible',
        \App\Models\Listing::STATUS_SOLD_OUT_TODAY => 'Agotado por hoy',
        \App\Models\Listing::STATUS_RESERVED => 'Reservado',
        \App\Models\Listing::STATUS_SOLD => 'Vendido',
        \App\Models\Listing::STATUS_EXCHANGED => 'Intercambiado',
    ];
@endphp

<article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <a href="{{ route('marketplace.show', $listing) }}" class="block" aria-label="Ver {{ $listing->title }}">
        <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
            @if ($cover)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($cover->path) }}" alt="{{ $cover->alt_text ?: $listing->title }}" class="size-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
            @else
                <div class="flex size-full items-center justify-center bg-gradient-to-br from-blue-50 to-slate-100 text-sm font-semibold text-slate-400">Sin fotografía</div>
            @endif
            <span class="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-1 text-xs font-bold text-slate-700 shadow-sm">{{ $statusLabels[$listing->status] ?? $listing->status }}</span>
        </div>
        <div class="p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-blue-700">{{ $listing->category->name }}</p>
            <h2 class="mt-2 line-clamp-2 min-h-12 font-display text-base font-bold text-blue-950">{{ $listing->title }}</h2>
            <div class="mt-3 flex items-center justify-between gap-3">
                @if ($listing->listing_type === \App\Models\Listing::TYPE_SALE && $listing->price !== null)
                    <p class="text-lg font-extrabold text-slate-900">${{ number_format((float) $listing->price, 2) }}</p>
                @else
                    <p class="text-sm font-bold text-blue-800">Intercambio</p>
                @endif
                @if ($listing->availability_type === \App\Models\Listing::AVAILABILITY_UNIQUE)
                    <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-bold text-violet-700">Artículo único</span>
                @else
                    <span class="rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-bold text-sky-700">Diaria</span>
                @endif
            </div>
            <div class="mt-4 flex items-center justify-between gap-2 border-t border-slate-100 pt-4 text-xs text-slate-500">
                <span class="truncate">{{ $listing->entrepreneurProfile?->business_name ?? $listing->user->name }}</span>
                <span class="truncate">{{ $listing->campus_location ?: 'Campus' }}</span>
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ $listing->favorited_by_count ?? 0 }} {{ ($listing->favorited_by_count ?? 0) === 1 ? 'favorito' : 'favoritos' }}</p>
        </div>
    </a>
    @auth
        @if (auth()->id() !== $listing->user_id && (($listing->is_favorited ?? false) || $listing->isAvailable()))
            <div class="px-5 pb-5">
                <form method="POST" action="{{ route(($listing->is_favorited ?? false) ? 'marketplace.unfavorite' : 'marketplace.favorite', $listing) }}">
                    @csrf
                    @if ($listing->is_favorited ?? false)
                        @method('DELETE')
                    @endif
                    <button type="submit" class="w-full rounded-xl border border-blue-100 px-4 py-2 text-sm font-bold text-blue-800 transition hover:bg-blue-50">
                        {{ ($listing->is_favorited ?? false) ? 'Quitar de favoritos' : 'Guardar en favoritos' }}
                    </button>
                </form>
            </div>
        @endif
    @endauth
</article>
