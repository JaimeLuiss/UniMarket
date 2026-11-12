@extends('layouts.app')

@section('title', 'Detalle del reporte')

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
<section class="mx-auto max-w-5xl space-y-7 px-5 py-8 sm:px-8 sm:py-12">
    @include('admin._navigation')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Administración · Reportes</p>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-blue-950">Detalle del reporte #{{ $report->id }}</h1>
        </div>
        <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700">{{ $statuses[$report->status] ?? $report->status }}</span>
    </div>

    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="font-display text-xl font-bold text-blue-950">Información del reporte</h2>
        <dl class="mt-5 grid gap-5 sm:grid-cols-2">
            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Motivo</dt><dd class="mt-1 font-semibold text-slate-800">{{ $reasons[$report->reason] ?? $report->reason }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Fecha de creación</dt><dd class="mt-1 font-semibold text-slate-800">{{ $report->created_at->format('d/m/Y H:i') }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Usuario que reportó</dt><dd class="mt-1 font-semibold text-slate-800">{{ $report->reporter->name }} <span class="font-normal text-slate-500">· {{ $report->reporter->email }}</span></dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Usuario reportado</dt><dd class="mt-1 font-semibold text-slate-800">{{ $report->reportedUser?->name ?? 'Cuenta no disponible' }} @if($report->reportedUser)<span class="font-normal text-slate-500">· {{ $report->reportedUser->email }}</span>@endif</dd></div>
        </dl>
        <div class="mt-6">
            <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Descripción enviada</h3>
            <p class="mt-2 whitespace-pre-line rounded-xl bg-slate-50 p-4 text-sm leading-6 text-slate-700">{{ $report->description }}</p>
        </div>
    </article>

    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="font-display text-xl font-bold text-blue-950">Publicación denunciada</h2>
        @if ($report->listing)
            <div class="mt-5 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="font-bold text-slate-800">{{ $report->listing->title }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $report->listing->category?->name ?? 'Sin categoría' }}</p>
                    <p class="mt-3 max-w-2xl whitespace-pre-line text-sm leading-6 text-slate-600">{{ $report->listing->description }}</p>
                    <p class="mt-3 text-sm text-slate-600">Estado comercial: <strong>{{ $commercialStatusLabels[$report->listing->status] ?? $report->listing->status }}</strong></p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $report->listing->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800' }}">{{ $report->listing->is_active ? 'Activa' : 'Desactivada por moderación' }}</span>
            </div>
            @if ($report->listing->is_active)
                <form method="POST" action="{{ route('admin.reports.listing-moderation', $report) }}" class="mt-5" onsubmit="return confirm('¿Desactivar esta publicación en el marketplace? No cambiará su estado comercial.')">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_active" value="0">
                    <button class="rounded-xl bg-red-700 px-5 py-3 text-sm font-bold text-white hover:bg-red-800">Desactivar publicación</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.reports.listing-moderation', $report) }}" class="mt-5" onsubmit="return confirm('¿Reactivar esta publicación en el marketplace?')">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_active" value="1">
                    <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Reactivar publicación</button>
                </form>
            @endif
        @else
            <p class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">La publicación fue eliminada. El reporte se conserva para su revisión.</p>
        @endif
    </article>

    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="font-display text-xl font-bold text-blue-950">Historial de revisión</h2>
        @if ($report->reviewed_at)
            <p class="mt-3 text-sm text-slate-600">Última actualización: {{ $report->reviewed_at->format('d/m/Y H:i') }} por {{ $report->reviewer?->name ?? 'Administrador no disponible' }}</p>
        @endif
        @if ($report->resolution_notes)
            <pre class="mt-4 whitespace-pre-wrap break-words rounded-xl bg-slate-50 p-4 font-sans text-sm leading-6 text-slate-700">{{ $report->resolution_notes }}</pre>
        @else
            <p class="mt-3 text-sm text-slate-500">Todavía no hay observaciones administrativas.</p>
        @endif
    </article>

    @if (in_array($report->status, [\App\Models\Report::STATUS_PENDING, \App\Models\Report::STATUS_REVIEWED], true))
        <section class="grid gap-5 lg:grid-cols-3">
            @if ($report->status === \App\Models\Report::STATUS_PENDING)
                <form method="POST" action="{{ route('admin.reports.review', $report) }}" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5">
                    @csrf
                    @method('PATCH')
                    <h2 class="font-bold text-blue-950">Marcar como revisado</h2>
                    <label class="sr-only" for="review_notes">Observaciones de revisión</label>
                    <textarea id="review_notes" name="resolution_notes" rows="3" maxlength="2000" placeholder="Observaciones (opcional)" class="w-full rounded-xl border-slate-300 text-sm"></textarea>
                    <button class="w-full rounded-xl bg-blue-800 px-4 py-3 text-sm font-bold text-white hover:bg-blue-900">Registrar revisión</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.reports.resolve', $report) }}" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5" onsubmit="return confirm('¿Resolver este reporte?')">
                @csrf
                @method('PATCH')
                <h2 class="font-bold text-emerald-900">Resolver reporte</h2>
                <label class="sr-only" for="resolve_notes">Observaciones de resolución</label>
                <textarea id="resolve_notes" name="resolution_notes" rows="3" maxlength="2000" placeholder="Observaciones (opcional)" class="w-full rounded-xl border-slate-300 text-sm"></textarea>
                <button class="w-full rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-800">Resolver</button>
            </form>
            <form method="POST" action="{{ route('admin.reports.reject', $report) }}" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5" onsubmit="return confirm('¿Rechazar este reporte?')">
                @csrf
                @method('PATCH')
                <h2 class="font-bold text-slate-800">Rechazar reporte</h2>
                <label class="sr-only" for="reject_notes">Observaciones de rechazo</label>
                <textarea id="reject_notes" name="resolution_notes" rows="3" maxlength="2000" placeholder="Observaciones (opcional)" class="w-full rounded-xl border-slate-300 text-sm"></textarea>
                <button class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Rechazar</button>
            </form>
        </section>
    @endif
</section>
@endsection
