@extends('layouts.app')

@section('title', 'Nueva contraseña')

@section('content')
<section class="mx-auto flex min-h-[calc(100vh-145px)] max-w-3xl items-center px-5 py-12 sm:px-8">
    <div class="w-full rounded-3xl border border-slate-200 bg-white p-7 shadow-sm sm:p-12">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Acceso a tu cuenta</p>
        <h1 class="mt-3 font-display text-3xl font-extrabold tracking-tight text-blue-950 sm:text-4xl">Crea una nueva contraseña</h1>
        <p class="mt-4 text-base leading-7 text-slate-600">Elige una contraseña segura de al menos 8 caracteres.</p>

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="mt-8 max-w-lg space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required class="form-input">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Nueva contraseña</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" class="form-input">
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-700">Confirmar contraseña</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8" class="form-input">
            </div>
            <button type="submit" class="w-full rounded-xl bg-blue-800 px-5 py-3.5 text-sm font-bold text-white transition hover:bg-blue-900 sm:w-auto">Actualizar contraseña</button>
        </form>
    </div>
</section>
@endsection
