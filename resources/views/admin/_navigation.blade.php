<nav class="rounded-2xl border border-blue-100 bg-white p-3 shadow-sm" aria-label="Navegación administrativa">
    <div class="flex flex-wrap gap-2 text-sm font-semibold">
        <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2 text-blue-900 transition hover:bg-blue-50">Dashboard</a>
        <a href="{{ route('admin.users.index') }}" class="rounded-lg px-3 py-2 text-blue-900 transition hover:bg-blue-50">Usuarios</a>
        <a href="{{ route('admin.categories.index') }}" class="rounded-lg px-3 py-2 text-blue-900 transition hover:bg-blue-50">Categorías</a>
        <a href="{{ route('admin.listings.index') }}" class="rounded-lg px-3 py-2 text-blue-900 transition hover:bg-blue-50">Publicaciones</a>
        <a href="{{ route('admin.reports.index') }}" class="rounded-lg px-3 py-2 text-blue-900 transition hover:bg-blue-50">Reportes</a>
        <a href="{{ route('marketplace.index') }}" class="ml-auto rounded-lg px-3 py-2 text-slate-600 transition hover:bg-slate-50 hover:text-blue-800">Volver al marketplace</a>
    </div>
</nav>

@if (session('status'))
    <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif
