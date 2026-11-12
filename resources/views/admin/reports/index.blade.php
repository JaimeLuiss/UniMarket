@extends('layouts.app')

@section('title', 'Administrar reportes')

@section('content')
@php
    $statusLabels = [
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
        <h1 class="mt-2 font-display text-3xl font-extrabold text-blue-950">Reportes</h1>
        <p class="mt-2 text-sm text-slate-600">Revisa las denuncias enviadas por la comunidad.</p>
    </div>

    <form method="GET" action="{{ route('admin.reports.index') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-[1fr_12rem_14rem_10rem_auto]">
        <label class="sr-only" for="report_search">Buscar publicación o usuario</label>
        <input id="report_search" name="search" value="{{ $filters['search'] ?? '' }}" type="search" maxlength="120" placeholder="Buscar publicación o usuario" class="rounded-xl border-slate-300 text-sm focus:border-blue-700 focus:ring-blue-700">
        <label class="sr-only" for="report_status">Estado</label>
        <select id="report_status" name="status" class="rounded-xl border-slate-300 text-sm focus:border-blue-700 focus:ring-blue-700">
            <option value="">Todos los estados</option>
            @foreach ([
                \App\Models\Report::STATUS_PENDING => 'Pendiente',
                \App\Models\Report::STATUS_REVIEWED => 'Revisado',
                \App\Models\Report::STATUS_RESOLVED => 'Resuelto',
                \App\Models\Report::STATUS_REJECTED => 'Rechazado',
            ] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <label class="sr-only" for="report_reason">Motivo</label>
        <select id="report_reason" name="reason" class="rounded-xl border-slate-300 text-sm focus:border-blue-700 focus:ring-blue-700">
            <option value="">Todos los motivos</option>
            @foreach ($reasons as $value => $label)
                <option value="{{ $value }}" @selected(($filters['reason'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <label class="sr-only" for="report_sort">Orden</label>
        <select id="report_sort" name="sort" class="rounded-xl border-slate-300 text-sm focus:border-blue-700 focus:ring-blue-700">
            <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Más recientes</option>
            <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Más antiguos</option>
        </select>
        <button class="rounded-xl bg-blue-800 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-900">Filtrar</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-4">Publicación</th><th class="px-5 py-4">Motivo</th><th class="px-5 py-4">Reportado por</th><th class="px-5 py-4">Usuario reportado</th><th class="px-5 py-4">Estado</th><th class="px-5 py-4">Fecha</th><th class="px-5 py-4"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($reports as $report)
                        <tr>
                            <td class="px-5 py-4 font-semibold text-slate-800">{{ $report->listing?->title ?? 'Publicación eliminada' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $reasons[$report->reason] ?? $report->reason }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $report->reporter->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $report->reportedUser?->name ?? 'Cuenta no disponible' }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $report->status === \App\Models\Report::STATUS_PENDING ? 'bg-amber-50 text-amber-800' : ($report->status === \App\Models\Report::STATUS_RESOLVED ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-700') }}">{{ $statusLabels[$report->status] ?? $report->status }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $report->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4"><a href="{{ route('admin.reports.show', $report) }}" class="font-bold text-blue-800 hover:text-blue-950">Revisar</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center text-slate-500">No hay reportes que coincidan con los filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $reports->links() }}
</section>
@endsection
