@extends('layouts.app')

@section('title', 'Marketplace universitario')

@section('content')
<section class="mx-auto max-w-7xl px-5 pb-16 pt-12 sm:px-8 sm:pb-24 sm:pt-20">
    <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr]">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-white px-4 py-2 text-xs font-bold uppercase tracking-[0.14em] text-blue-800">
                <span class="size-2 rounded-full bg-sky-500"></span>
                Hecho para la comunidad universitaria
            </span>
            <h1 class="mt-7 max-w-2xl font-display text-5xl font-extrabold leading-[1.08] tracking-tight text-blue-950 sm:text-6xl">Tu campus tiene mucho <span class="text-blue-700">para compartir.</span></h1>
            <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">UniMarket conecta a estudiantes y emprendimientos para comprar, vender e intercambiar dentro de la comunidad universitaria.</p>
            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-800 px-6 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-900">Crear mi cuenta</a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-6 py-3.5 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:text-blue-800">Ya tengo una cuenta</a>
            </div>
            <p class="mt-5 text-xs text-slate-500">El acceso al marketplace estará disponible próximamente para estudiantes registrados.</p>
        </div>

        <div class="relative">
            <div class="absolute -inset-4 rounded-[2rem] bg-blue-100/70 blur-2xl"></div>
            <div class="relative overflow-hidden rounded-[2rem] bg-blue-950 p-7 text-white shadow-xl sm:p-10">
                <div class="flex items-start justify-between gap-5">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.16em] text-sky-300">Dentro de tu campus</p>
                        <h2 class="mt-3 font-display text-2xl font-extrabold">Ideas que se encuentran.</h2>
                    </div>
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-lg font-extrabold">UM</span>
                </div>
                <div class="mt-9 space-y-3">
                    @foreach ([
                        ['01', 'Descubre', 'Productos y servicios de estudiantes.'],
                        ['02', 'Conecta', 'Habla directamente con tu comunidad.'],
                        ['03', 'Comparte', 'Haz crecer los emprendimientos del campus.'],
                    ] as [$number, $title, $description])
                        <div class="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/5 p-4">
                            <span class="text-xs font-bold tracking-widest text-sky-300">{{ $number }}</span>
                            <div>
                                <h3 class="font-bold">{{ $title }}</h3>
                                <p class="mt-1 text-sm text-blue-100/70">{{ $description }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-7 border-t border-white/10 pt-5 text-xs font-medium text-blue-100/60">UniMarket · Una comunidad que se mueve contigo.</div>
            </div>
        </div>
    </div>
</section>
@endsection
