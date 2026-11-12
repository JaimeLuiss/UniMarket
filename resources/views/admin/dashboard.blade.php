@extends('layouts.app')

@section('title', 'Administración')

@section('content')
@php
    $reportStatusLabels = [
        \App\Models\Report::STATUS_PENDING => 'Pendiente',
        \App\Models\Report::STATUS_REVIEWED => 'Revisado',
        \App\Models\Report::STATUS_RESOLVED => 'Resuelto',
        \App\Models\Report::STATUS_REJECTED => 'Rechazado',
    ];
@endphp
<section class="mx-auto max-w-7xl space-y-7 px-5 py-8 sm:px-8 sm:py-12">
    @include('admin._navigation')

    <div>
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Administración</p>
        <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-blue-950">Panel administrativo</h1>
        <p class="mt-2 text-sm text-slate-600">Resumen actualizado de la actividad de UniMarket.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            ['Usuarios registrados', $metrics['users'], 'bg-blue-50 text-blue-900'],
            ['Publicaciones', $metrics['listings'], 'bg-slate-100 text-slate-900'],
            ['Publicaciones disponibles', $metrics['availableListings'], 'bg-emerald-50 text-emerald-900'],
            ['Emprendimientos activos', $metrics['activeEntrepreneurs'], 'bg-sky-50 text-sky-900'],
            ['Reportes pendientes', $metrics['pendingReports'], 'bg-amber-50 text-amber-900'],
            ['Reportes resueltos', $metrics['resolvedReports'], 'bg-violet-50 text-violet-900'],
        ] as [$label, $value, $color])
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-600">{{ $label }}</p>
                <p class="mt-3 inline-flex rounded-xl px-4 py-2 font-display text-3xl font-extrabold {{ $color }}">{{ number_format($value) }}</p>
            </article>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-display text-xl font-bold text-blue-950">Publicaciones recientes</h2>
                <a href="{{ route('admin.listings.index') }}" class="text-sm font-bold text-blue-800 hover:text-blue-950">Ver todas</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($recentListings as $listing)
                    <div class="flex items-start justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-800">{{ $listing->title }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $listing->category->name }} · {{ $listing->user->name }}</p>
                        </div>
                        <span class="shrink-0 text-xs text-slate-500">{{ $listing->created_at->format('d/m/Y') }}</span>
                    </div>
                @empty
                    <p class="py-5 text-sm text-slate-500">Aún no hay publicaciones.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-display text-xl font-bold text-blue-950">Últimos reportes</h2>
                <a href="{{ route('admin.reports.index') }}" class="text-sm font-bold text-blue-800 hover:text-blue-950">Revisar reportes</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($recentReports as $report)
                    <a href="{{ route('admin.reports.show', $report) }}" class="flex items-start justify-between gap-4 py-3">
                        <span class="min-w-0">
                            <span class="block truncate font-semibold text-slate-800">{{ $report->listing?->title ?? 'Publicación eliminada' }}</span>
                            <span class="mt-1 block text-sm text-slate-500">{{ \App\Models\Report::REASON_LABELS[$report->reason] ?? $report->reason }} · {{ $report->reporter->name }}</span>
                        </span>
                        <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800">{{ $reportStatusLabels[$report->status] ?? $report->status }}</span>
                    </a>
                @empty
                    <p class="py-5 text-sm text-slate-500">Aún no se han recibido reportes.</p>
                @endforelse
            </div>
        </section>
    </div>
</section>
@endsection
