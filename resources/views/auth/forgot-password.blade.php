@extends('layouts.app')

@section('title', 'Recuperar contraseña')

@section('content')
<section class="mx-auto flex min-h-[calc(100vh-145px)] max-w-3xl items-center px-5 py-12 sm:px-8">
    <div class="w-full rounded-3xl border border-slate-200 bg-white p-7 shadow-sm sm:p-12">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Recuperación segura</p>
        <h1 class="mt-3 font-display text-3xl font-extrabold tracking-tight text-blue-950 sm:text-4xl">¿Olvidaste tu contraseña?</h1>
        <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">Ingresa el correo asociado a tu cuenta y te enviaremos un enlace temporal para crear una nueva contraseña.</p>

        @if (session('status'))
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif
        @error('email') <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $message }}</div> @enderror

        <form method="POST" action="{{ route('password.email') }}" class="mt-8 max-w-lg space-y-5">
            @csrf
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="form-input" placeholder="nombre@universidad.edu">
            </div>
            <button type="submit" class="w-full rounded-xl bg-blue-800 px-5 py-3.5 text-sm font-bold text-white transition hover:bg-blue-900 sm:w-auto">Enviar enlace de recuperación</button>
        </form>
        <a href="{{ route('login') }}" class="mt-7 inline-block text-sm font-bold text-blue-800 hover:text-blue-950">Volver a iniciar sesión</a>
    </div>
</section>
@endsection
