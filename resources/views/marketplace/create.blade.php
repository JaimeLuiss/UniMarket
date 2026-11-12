@extends('layouts.app')

@section('title', 'Crear publicación')

@section('content')
<section class="mx-auto max-w-4xl px-5 py-10 sm:px-8 sm:py-14">
    <a href="{{ route('marketplace.index') }}" class="text-sm font-bold text-blue-800 hover:text-blue-950">← Volver al marketplace</a>
    <div class="mt-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-blue-700">Comparte con tu campus</p>
        <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-blue-950">Crear publicación</h1>
        <p class="mt-3 text-sm leading-6 text-slate-500">Completa los detalles. Tu cuenta será automáticamente la propietaria de esta publicación.</p>
        @include('marketplace._form', ['listing' => null])
    </div>
</section>
@endsection
