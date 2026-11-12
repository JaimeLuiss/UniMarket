@extends('layouts.app')

@section('title', $profile ? 'Editar emprendimiento' : 'Crear emprendimiento')

@section('content')
<section class="mx-auto max-w-3xl px-5 py-9 sm:px-8 sm:py-12">
    <a href="{{ route('my-entrepreneur') }}" class="text-sm font-bold text-blue-800 hover:text-blue-950">← Mi emprendimiento</a>
    <div class="mt-5">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-blue-700">Tu negocio en el campus</p>
        <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-blue-950">{{ $profile ? 'Editar perfil' : 'Crear perfil de emprendimiento' }}</h1>
        <p class="mt-2 text-sm text-slate-500">Completa la información que podrán consultar otros estudiantes.</p>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ $profile ? route('my-entrepreneur.update') : route('my-entrepreneur.store') }}" enctype="multipart/form-data" class="mt-7 space-y-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        @csrf
        @if ($profile)
            @method('PUT')
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div>
            <label for="business_name" class="mb-2 block text-sm font-semibold text-slate-700">Nombre comercial</label>
            <input id="business_name" name="business_name" value="{{ old('business_name', $profile?->business_name) }}" required maxlength="255" class="form-input">
            @error('business_name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="mb-2 block text-sm font-semibold text-slate-700">Descripción</label>
            <textarea id="description" name="description" rows="5" required maxlength="10000" class="form-input" placeholder="Describe tu emprendimiento y lo que ofreces.">{{ old('description', $profile?->description) }}</textarea>
            @error('description') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="campus_location" class="mb-2 block text-sm font-semibold text-slate-700">Ubicación en el campus <span class="font-normal text-slate-400">(opcional)</span></label>
            <input id="campus_location" name="campus_location" value="{{ old('campus_location', $profile?->campus_location) }}" maxlength="255" class="form-input" placeholder="Ej. Edificio de Ingeniería">
            @error('campus_location') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        @if ($profile?->logo_path)
            <div>
                <p class="mb-2 text-sm font-semibold text-slate-700">Logotipo actual</p>
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($profile->logo_path) }}" alt="Logotipo de {{ $profile->business_name }}" class="size-24 rounded-xl border border-slate-200 object-cover">
            </div>
        @endif
        <div>
            <label for="logo" class="mb-2 block text-sm font-semibold text-slate-700">Logotipo <span class="font-normal text-slate-400">(JPEG, PNG o WebP; máximo 2 MB)</span></label>
            <input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-bold file:text-blue-800">
            @error('logo') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        @if ($profile)
            <label class="flex items-start gap-3 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $profile->is_active)) class="mt-0.5 size-4 rounded border-slate-300 text-blue-800">
                <span><span class="block font-bold">Emprendimiento activo</span><span class="mt-1 block text-xs text-slate-500">Los perfiles inactivos no aparecen públicamente ni pueden asociarse a nuevas publicaciones.</span></span>
            </label>
        @else
            <input type="hidden" name="is_active" value="1">
        @endif

        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ route('my-entrepreneur') }}" class="inline-flex justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 hover:text-blue-800">Cancelar</a>
            <button type="submit" class="inline-flex justify-center rounded-xl bg-blue-800 px-6 py-3 text-sm font-bold text-white hover:bg-blue-900">{{ $profile ? 'Guardar cambios' : 'Crear perfil' }}</button>
        </div>
    </form>
</section>
@endsection
