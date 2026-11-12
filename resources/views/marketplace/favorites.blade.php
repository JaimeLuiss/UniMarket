@extends('layouts.app')

@section('title', 'Mis favoritos')

@section('content')
<section class="mx-auto max-w-7xl px-5 py-9 sm:px-8 sm:py-12">
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-blue-700">Tu colección</p>
        <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-blue-950 sm:text-4xl">Mis favoritos</h1>
        <p class="mt-2 text-sm text-slate-500">Publicaciones disponibles que guardaste para consultar después.</p>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    @if ($listings->isEmpty())
        <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <h2 class="font-display text-xl font-bold text-blue-950">Todavía no tienes favoritos</h2>
            <p class="mt-2 text-sm text-slate-500">Guarda publicaciones activas desde el marketplace para encontrarlas aquí.</p>
            <a href="{{ route('marketplace.index') }}" class="mt-5 inline-flex rounded-xl bg-blue-800 px-5 py-3 text-sm font-bold text-white hover:bg-blue-900">Explorar marketplace</a>
        </div>
    @else
        <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($listings as $listing)
                <x-listing-card :listing="$listing" />
            @endforeach
        </div>
        <div class="mt-8">{{ $listings->links() }}</div>
    @endif
</section>
@endsection
