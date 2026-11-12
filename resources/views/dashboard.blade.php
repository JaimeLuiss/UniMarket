@extends('layouts.app')

@section('title', 'Mi espacio')

@section('content')
<section class="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
    <div class="overflow-hidden rounded-3xl bg-blue-950 px-7 py-9 text-white sm:px-10 sm:py-12">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-sky-300">Tu espacio universitario</p>
        <h1 class="mt-3 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">¡Bienvenido, {{ auth()->user()->name }}!</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-blue-100/80 sm:text-base">Desde aquí podrás organizar tu actividad en UniMarket. Estamos preparando nuevas herramientas para tu comunidad.</p>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    <div class="mt-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-blue-700">Panel personal</p>
                <h2 class="mt-2 font-display text-2xl font-extrabold text-blue-950">Tus accesos</h2>
            </div>
            <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold capitalize text-blue-800">{{ auth()->user()->role }}</span>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Mis publicaciones', 'Administra los productos o servicios que compartes con el campus.', 'Próximamente'],
                ['Mis favoritos', 'Encuentra en un solo lugar las publicaciones que guardaste.', 'Próximamente'],
                ['Mi perfil', 'Consulta y actualiza la información de tu cuenta.', 'Próximamente'],
                ['Marketplace', 'Muy pronto podrás descubrir las publicaciones de tu comunidad.', 'En preparación'],
            ] as [$title, $description, $status])
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-blue-50 text-lg font-bold text-blue-800" aria-hidden="true">+</div>
                    <h3 class="mt-5 font-display text-lg font-bold text-blue-950">{{ $title }}</h3>
                    <p class="mt-2 min-h-12 text-sm leading-6 text-slate-500">{{ $description }}</p>
                    <span class="mt-5 inline-flex rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">{{ $status }}</span>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endsection
