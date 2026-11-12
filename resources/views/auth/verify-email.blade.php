@extends('layouts.app')

@section('title', 'Verifica tu correo')

@section('content')
<section class="mx-auto flex min-h-[calc(100vh-145px)] max-w-3xl items-center px-5 py-12 sm:px-8">
    <div class="w-full rounded-3xl border border-slate-200 bg-white p-7 shadow-sm sm:p-12">
        <span class="flex size-14 items-center justify-center rounded-2xl bg-blue-50 text-2xl text-blue-800" aria-hidden="true">@</span>
        <p class="mt-8 text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Un último paso</p>
        <h1 class="mt-3 font-display text-3xl font-extrabold tracking-tight text-blue-950 sm:text-4xl">Verifica tu correo</h1>
        <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">Enviamos un enlace de verificación a <strong class="text-slate-800">{{ auth()->user()->email }}</strong>. Confirma tu dirección para proteger tu cuenta y acceder a UniMarket.</p>

        @if (session('status'))
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <a href="{{ $verificationUrl }}" class="inline-flex items-center justify-center rounded-xl bg-blue-800 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-900">Abrir enlace de verificación</a>
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="w-full rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:text-blue-800">Reenviar correo</button>
            </form>
        </div>
        <p class="mt-6 text-xs leading-5 text-slate-500">El enlace es personal y temporal. Si no encuentras el mensaje, revisa también tu carpeta de correo no deseado.</p>
    </div>
</section>
@endsection
