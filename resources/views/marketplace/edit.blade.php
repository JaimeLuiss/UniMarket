@extends('layouts.app')

@section('title', 'Editar publicación')

@section('content')
<section class="mx-auto max-w-4xl px-5 py-10 sm:px-8 sm:py-14">
    <a href="{{ route('my-listings') }}" class="text-sm font-bold text-blue-800 hover:text-blue-950">← Volver a mis publicaciones</a>
    <div class="mt-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-blue-700">Gestiona tu publicación</p>
        <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-blue-950">Editar publicación</h1>
        <div class="mt-7">
            @include('marketplace._form', ['listing' => $listing])
        </div>
    </div>
</section>
@endsection
