@extends('layouts.app')

@section('title', $profile->business_name)

@section('content')
<section class="mx-auto max-w-7xl px-5 py-9 sm:px-8 sm:py-12">
    <a href="{{ route('marketplace.index') }}" class="text-sm font-bold text-blue-800 hover:text-blue-950">← Volver al marketplace</a>

    @if (session('status'))
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <article class="mt-6 grid gap-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-[144px_1fr] sm:p-9">
        @if ($profile->logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($profile->logo_path) }}" alt="Logotipo de {{ $profile->business_name }}" class="size-32 rounded-2xl border border-slate-200 object-cover">
        @else
            <div class="flex size-32 items-center justify-center rounded-2xl bg-blue-50 font-display text-4xl font-extrabold text-blue-800">{{ str($profile->business_name)->substr(0, 1)->upper() }}</div>
        @endif
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-blue-700">Emprendimiento universitario</p>
            <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-blue-950 sm:text-4xl">{{ $profile->business_name }}</h1>
            <p class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $profile->description }}</p>
            <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-slate-500">
                <span>{{ $profile->campus_location ?: 'Ubicación por coordinar en campus' }}</span>
                <span>{{ $profile->followers_count }} {{ $profile->followers_count === 1 ? 'seguidor' : 'seguidores' }}</span>
            </div>
            @if (auth()->id() !== $profile->user_id)
                <form method="POST" action="{{ route($isFollowing ? 'entrepreneurs.unfollow' : 'entrepreneurs.follow', $profile) }}" class="mt-5">
                    @csrf
                    @if ($isFollowing)
                        @method('DELETE')
                    @endif
                    <button type="submit" class="rounded-xl {{ $isFollowing ? 'border border-blue-100 bg-white text-blue-800 hover:bg-blue-50' : 'bg-blue-800 text-white hover:bg-blue-900' }} px-5 py-3 text-sm font-bold transition">
                        {{ $isFollowing ? 'Dejar de seguir' : 'Seguir emprendimiento' }}
                    </button>
                </form>
            @endif
        </div>
    </article>

    <div class="mt-10 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-blue-950">Productos y servicios</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $listings->total() }} publicaciones del emprendimiento</p>
        </div>
    </div>
    @if ($listings->isEmpty())
        <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center text-sm text-slate-500">Este emprendimiento aún no tiene publicaciones.</div>
    @else
        <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($listings as $listing)
                <x-listing-card :listing="$listing" />
            @endforeach
        </div>
        <div class="mt-8">{{ $listings->links() }}</div>
    @endif
</section>
@endsection
