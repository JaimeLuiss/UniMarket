@extends('layouts.app')

@section('title', 'Crear cuenta')

@section('content')
<section class="mx-auto grid min-h-[calc(100vh-145px)] max-w-7xl items-center gap-12 px-5 py-12 sm:px-8 lg:grid-cols-[0.9fr_1.1fr] lg:py-16">
    <div class="hidden rounded-3xl bg-blue-950 p-10 text-white lg:block xl:p-14">
        <div class="mb-16 inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold tracking-wide text-blue-100">TU COMUNIDAD, TU CAMPUS</div>
        <h1 class="font-display text-4xl font-extrabold leading-tight xl:text-5xl">Lo que buscas,<br>está más cerca.</h1>
        <p class="mt-6 max-w-md text-base leading-7 text-blue-100/80">Conecta con estudiantes, descubre emprendimientos y encuentra lo que necesitas dentro de tu comunidad universitaria.</p>
        <div class="mt-12 grid grid-cols-3 gap-3 text-center text-xs font-semibold text-blue-100">
            <div class="rounded-2xl border border-white/10 bg-white/5 px-3 py-5">Compra local</div>
            <div class="rounded-2xl border border-white/10 bg-white/5 px-3 py-5">Comparte más</div>
            <div class="rounded-2xl border border-white/10 bg-white/5 px-3 py-5">Crece aquí</div>
        </div>
    </div>

    <div class="mx-auto w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Únete a UniMarket</p>
        <h2 class="mt-3 font-display text-3xl font-extrabold tracking-tight text-blue-950">Crea tu cuenta</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Regístrate con tus datos para explorar el marketplace universitario.</p>

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <p class="font-bold">Revisa la información ingresada:</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="mt-7 space-y-5">
            @csrf
            <div>
                <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Nombre completo</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus maxlength="255" class="form-input" placeholder="Tu nombre y apellido">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255" class="form-input" placeholder="nombre@universidad.edu">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="mb-2 block text-sm font-semibold text-slate-700">Teléfono <span class="font-normal text-slate-400">(opcional)</span></label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" maxlength="30" class="form-input" placeholder="+57 300 000 0000">
                @error('phone') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Contraseña</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" class="form-input" placeholder="Mínimo 8 caracteres">
                    @error('password') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-700">Confirmar contraseña</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8" class="form-input" placeholder="Repite tu contraseña">
                </div>
            </div>
            <div>
                <label class="flex items-start gap-3 text-sm leading-6 text-slate-600">
                    <input type="checkbox" name="terms" value="1" required @checked(old('terms')) class="mt-1 size-4 rounded border-slate-300 text-blue-800 focus:ring-blue-700">
                    <span>Acepto los términos y condiciones de uso de UniMarket.</span>
                </label>
                @error('terms') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="w-full rounded-xl bg-blue-800 px-5 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-900 focus:outline-none focus:ring-4 focus:ring-blue-200">Crear cuenta</button>
        </form>

        <p class="mt-7 text-center text-sm text-slate-500">¿Ya tienes una cuenta? <a href="{{ route('login') }}" class="font-bold text-blue-800 hover:text-blue-950">Inicia sesión</a></p>
    </div>
</section>
@endsection
