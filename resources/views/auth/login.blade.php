@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
<section class="mx-auto grid min-h-[calc(100vh-145px)] max-w-7xl items-center gap-12 px-5 py-12 sm:px-8 lg:grid-cols-[1.05fr_0.95fr] lg:py-16">
    <div class="hidden lg:block">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Marketplace universitario</p>
        <h1 class="mt-5 max-w-xl font-display text-5xl font-extrabold leading-tight tracking-tight text-blue-950 xl:text-6xl">Todo lo que mueve tu campus.</h1>
        <p class="mt-6 max-w-lg text-lg leading-8 text-slate-600">Ingresa para conectar con estudiantes y emprendimientos de tu comunidad.</p>
        <div class="mt-10 flex items-center gap-3 text-sm font-semibold text-slate-500">
            <span class="size-2 rounded-full bg-sky-500"></span>
            Tu comunidad universitaria, en un solo lugar
        </div>
    </div>

    <div class="mx-auto w-full max-w-md rounded-3xl border border-slate-200 bg-white p-7 shadow-sm sm:p-10">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Qué bueno verte</p>
        <h2 class="mt-3 font-display text-3xl font-extrabold tracking-tight text-blue-950">Inicia sesión</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Ingresa a tu cuenta para continuar.</p>

        @if (session('status'))
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5">
            @csrf
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus class="form-input" placeholder="nombre@universidad.edu">
            </div>
            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <label for="password" class="block text-sm font-semibold text-slate-700">Contraseña</label>
                    <a href="{{ route('password.request') }}" class="text-xs font-bold text-blue-800 hover:text-blue-950">¿La olvidaste?</a>
                </div>
                <input id="password" name="password" type="password" autocomplete="current-password" required class="form-input" placeholder="Tu contraseña">
            </div>
            <label class="flex items-center gap-3 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="size-4 rounded border-slate-300 text-blue-800 focus:ring-blue-700">
                Mantener mi sesión iniciada
            </label>
            <button type="submit" class="w-full rounded-xl bg-blue-800 px-5 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-900 focus:outline-none focus:ring-4 focus:ring-blue-200">Ingresar</button>
        </form>
        <p class="mt-7 text-center text-sm text-slate-500">¿Aún no tienes cuenta? <a href="{{ route('register') }}" class="font-bold text-blue-800 hover:text-blue-950">Regístrate</a></p>
    </div>
</section>
@endsection
