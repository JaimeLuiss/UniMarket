@extends('layouts.app')

@section('title', 'Administrar usuarios')

@section('content')
<section class="mx-auto max-w-7xl space-y-7 px-5 py-8 sm:px-8 sm:py-12">
    @include('admin._navigation')
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Administración</p>
        <h1 class="mt-2 font-display text-3xl font-extrabold text-blue-950">Usuarios</h1>
        <p class="mt-2 text-sm text-slate-600">Consulta cuentas, verificación y actividad de publicaciones.</p>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-[1fr_12rem_auto]">
        <label class="sr-only" for="user_search">Buscar por nombre o correo</label>
        <input id="user_search" name="search" value="{{ $filters['search'] ?? '' }}" type="search" maxlength="120" placeholder="Buscar por nombre o correo" class="rounded-xl border-slate-300 text-sm focus:border-blue-700 focus:ring-blue-700">
        <label class="sr-only" for="user_role">Filtrar por rol</label>
        <select id="user_role" name="role" class="rounded-xl border-slate-300 text-sm focus:border-blue-700 focus:ring-blue-700">
            <option value="">Todos los roles</option>
            @foreach (\App\Models\User::ROLES as $role)
                <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $role === \App\Models\User::ROLE_ADMIN ? 'Administrador' : 'Estudiante' }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-blue-800 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-900">Filtrar</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-4">Usuario</th><th class="px-5 py-4">Rol</th><th class="px-5 py-4">Verificación</th><th class="px-5 py-4">Publicaciones</th><th class="px-5 py-4">Registro</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-800">{{ $user->name }}</p>
                                <p class="mt-1 text-slate-500">{{ $user->email }}</p>
                            </td>
                            <td class="px-5 py-4"><span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold capitalize text-blue-800">{{ $user->role === \App\Models\User::ROLE_ADMIN ? 'Administrador' : 'Estudiante' }}</span></td>
                            <td class="px-5 py-4">
                                @if ($user->email_verified_at)
                                    <span class="font-semibold text-emerald-700">Verificado</span>
                                    <span class="mt-1 block text-xs text-slate-500">{{ $user->email_verified_at->format('d/m/Y') }}</span>
                                @else
                                    <span class="font-semibold text-amber-700">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-semibold text-slate-700">{{ $user->listings_count }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $user->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">No se encontraron usuarios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $users->links() }}
</section>
@endsection
