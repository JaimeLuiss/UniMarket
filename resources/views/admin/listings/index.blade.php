@extends('layouts.app')

@section('title', 'Administrar publicaciones')

@section('content')
@php
    $commercialStatusLabels = [
        \App\Models\Listing::STATUS_AVAILABLE => 'Disponible',
        \App\Models\Listing::STATUS_SOLD_OUT_TODAY => 'Agotado por hoy',
        \App\Models\Listing::STATUS_RESERVED => 'Reservado',
        \App\Models\Listing::STATUS_SOLD => 'Vendido',
        \App\Models\Listing::STATUS_EXCHANGED => 'Intercambiado',
    ];
@endphp
<section class="mx-auto max-w-7xl space-y-7 px-5 py-8 sm:px-8 sm:py-12">
    @include('admin._navigation')
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Administración</p>
        <h1 class="mt-2 font-display text-3xl font-extrabold text-blue-950">Publicaciones</h1>
        <p class="mt-2 text-sm text-slate-600">Consulta actividad y reportes asociados. La moderación se gestiona desde el detalle de cada reporte.</p>
    </div>

    <form method="GET" action="{{ route('admin.listings.index') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-[1fr_12rem_12rem_auto]">
        <label class="sr-only" for="listing_search">Buscar publicaciones o propietarios</label>
        <input id="listing_search" name="search" value="{{ $filters['search'] ?? '' }}" type="search" maxlength="120" placeholder="Buscar publicación o propietario" class="rounded-xl border-slate-300 text-sm focus:border-blue-700 focus:ring-blue-700">
        <label class="sr-only" for="listing_status">Estado comercial</label>
        <select id="listing_status" name="status" class="rounded-xl border-slate-300 text-sm focus:border-blue-700 focus:ring-blue-700">
            <option value="">Todos los estados</option>
            @foreach (\App\Models\Listing::STATUSES as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $commercialStatusLabels[$status] }}</option>
            @endforeach
        </select>
        <label class="sr-only" for="listing_active">Moderación</label>
        <select id="listing_active" name="active" class="rounded-xl border-slate-300 text-sm focus:border-blue-700 focus:ring-blue-700">
            <option value="">Todas</option>
            <option value="active" @selected(($filters['active'] ?? '') === 'active')>Activas</option>
            <option value="inactive" @selected(($filters['active'] ?? '') === 'inactive')>Desactivadas</option>
        </select>
        <button class="rounded-xl bg-blue-800 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-900">Filtrar</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-4">Publicación</th><th class="px-5 py-4">Propietario</th><th class="px-5 py-4">Estado comercial</th><th class="px-5 py-4">Moderación</th><th class="px-5 py-4">Reportes</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($listings as $listing)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-800">{{ $listing->title }}</p>
                                <p class="mt-1 text-slate-500">{{ $listing->category->name }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-700">{{ $listing->user->name }}</p>
                                <p class="mt-1 text-slate-500">{{ $listing->user->email }}</p>
                            </td>
                            <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ $commercialStatusLabels[$listing->status] ?? $listing->status }}</span></td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $listing->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800' }}">{{ $listing->is_active ? 'Activa' : 'Desactivada' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @if ($listing->reports_count > 0)
                                    <a href="{{ route('admin.reports.index', ['search' => $listing->title]) }}" class="font-bold text-blue-800 hover:text-blue-950">{{ $listing->reports_count }} · Ver</a>
                                @else
                                    <span class="text-slate-500">0</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">No se encontraron publicaciones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $listings->links() }}
</section>
@endsection
