@extends('layouts.app')

@section('title', 'Emprendimientos seguidos')

@section('content')
<section class="mx-auto max-w-7xl px-5 py-9 sm:px-8 sm:py-12">
    <p class="text-sm font-bold uppercase tracking-[0.16em] text-blue-700">Tu comunidad</p>
    <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-blue-950 sm:text-4xl">Emprendimientos seguidos</h1>
    <p class="mt-2 text-sm text-slate-500">Novedades de los negocios que te interesan.</p>

    @if (session('status'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    @if ($profiles->isEmpty())
        <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <h2 class="font-display text-xl font-bold text-blue-950">Aún no sigues emprendimientos</h2>
            <p class="mt-2 text-sm text-slate-500">Explora el marketplace y sigue los negocios de tu campus.</p>
            <a href="{{ route('marketplace.index') }}" class="mt-5 inline-flex rounded-xl bg-blue-800 px-5 py-3 text-sm font-bold text-white hover:bg-blue-900">Explorar marketplace</a>
        </div>
    @else
        <div class="mt-7 grid gap-4 md:grid-cols-2">
            @foreach ($profiles as $profile)
                <article class="flex flex-col justify-between gap-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center">
                    <div class="flex min-w-0 items-center gap-4">
                        @if ($profile->logo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($profile->logo_path) }}" alt="" class="size-16 shrink-0 rounded-xl object-cover">
                        @else
                            <div class="flex size-16 shrink-0 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-800">{{ str($profile->business_name)->substr(0, 1)->upper() }}</div>
                        @endif
                        <div class="min-w-0">
                            @if ($profile->is_active)
                                <a href="{{ route('entrepreneurs.show', $profile->slug) }}" class="font-display text-lg font-bold text-blue-950 hover:text-blue-700">{{ $profile->business_name }}</a>
                            @else
                                <h2 class="font-display text-lg font-bold text-slate-600">{{ $profile->business_name }} <span class="text-xs font-semibold">(inactivo)</span></h2>
                            @endif
                            <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $profile->description }}</p>
                            <p class="mt-2 text-xs text-slate-500">{{ $profile->campus_location ?: 'Campus' }} · {{ $profile->followers_count }} seguidores · {{ $profile->listings_count }} publicaciones</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('entrepreneurs.unfollow', $profile) }}" class="shrink-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:border-blue-200 hover:text-blue-800">Dejar de seguir</button>
                    </form>
                </article>
            @endforeach
        </div>
        <div class="mt-8">{{ $profiles->links() }}</div>
    @endif
</section>
@endsection
