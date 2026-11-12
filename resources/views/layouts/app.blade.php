<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'UniMarket') · UniMarket</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <header class="border-b border-slate-200/80 bg-white">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-8" aria-label="Navegación principal">
            <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="UniMarket, inicio">
                <span class="flex size-10 items-center justify-center rounded-xl bg-blue-950 text-sm font-extrabold tracking-tight text-white">UM</span>
                <span class="font-display text-xl font-extrabold tracking-tight text-blue-950">UniMarket</span>
            </a>
            <div class="flex items-center gap-3 text-sm font-semibold">
                @auth
                    @if (auth()->user()->hasVerifiedEmail())
                        <a href="{{ route('marketplace.index') }}" class="hidden text-slate-600 transition hover:text-blue-800 sm:inline">Marketplace</a>
                        <a href="{{ route('my-listings') }}" class="hidden text-slate-600 transition hover:text-blue-800 sm:inline">Mis publicaciones</a>
                        <a href="{{ route('dashboard') }}" class="hidden text-slate-600 transition hover:text-blue-800 sm:inline">Mi espacio</a>
                        <a href="{{ route('marketplace.create') }}" class="rounded-lg bg-blue-800 px-4 py-2 text-white transition hover:bg-blue-900">Publicar</a>
                    @else
                        <a href="{{ route('verification.notice') }}" class="hidden text-slate-600 transition hover:text-blue-800 sm:inline">Verificar correo</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-lg border border-slate-200 px-4 py-2 text-slate-700 transition hover:border-blue-200 hover:text-blue-800">Cerrar sesión</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-slate-600 transition hover:text-blue-800">Ingresar</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-blue-800 px-4 py-2 text-white transition hover:bg-blue-900">Crear cuenta</a>
                @endauth
            </div>
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="mx-auto max-w-7xl px-5 py-8 text-center text-xs text-slate-500 sm:px-8">
        UniMarket · Un espacio pensado para la comunidad universitaria.
    </footer>
</body>
</html>
