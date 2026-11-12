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
        <nav class="mx-auto max-w-7xl px-5 py-4 sm:px-8" aria-label="Navegación principal">
            <div class="flex items-center justify-between gap-4">
                <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-3" aria-label="UniMarket, inicio">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-blue-950 text-sm font-extrabold tracking-tight text-white">UM</span>
                    <span class="font-display text-xl font-extrabold tracking-tight text-blue-950">UniMarket</span>
                </a>
                <div class="flex items-center gap-3 text-sm font-semibold">
                    @auth
                        @if (auth()->user()->hasVerifiedEmail())
                            <a href="{{ route('marketplace.create') }}" class="rounded-lg bg-blue-800 px-4 py-2 text-white transition hover:bg-blue-900">Publicar</a>
                        @else
                            <a href="{{ route('verification.notice') }}" class="text-slate-600 transition hover:text-blue-800">Verificar correo</a>
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
            </div>
            @auth
                @if (auth()->user()->hasVerifiedEmail())
                    <div class="mt-4 flex gap-5 overflow-x-auto border-t border-slate-100 pt-3 text-sm font-semibold whitespace-nowrap">
                        <a href="{{ route('marketplace.index') }}" class="text-slate-600 transition hover:text-blue-800">Marketplace</a>
                        <a href="{{ route('my-listings') }}" class="text-slate-600 transition hover:text-blue-800">Mis publicaciones</a>
                        <a href="{{ route('my-favorites') }}" class="text-slate-600 transition hover:text-blue-800">Mis favoritos</a>
                        <a href="{{ route('my-entrepreneur') }}" class="text-slate-600 transition hover:text-blue-800">Mi emprendimiento</a>
                        <a href="{{ route('entrepreneurs.following') }}" class="text-slate-600 transition hover:text-blue-800">Emprendimientos seguidos</a>
                        @php
                            $unreadMessageCount = \App\Models\Message::query()
                                ->whereNull('read_at')
                                ->where('sender_id', '!=', auth()->id())
                                ->whereHas('conversation', fn ($query) => $query
                                    ->where('buyer_id', auth()->id())
                                    ->orWhere('seller_id', auth()->id()))
                                ->count();
                        @endphp
                        <a href="{{ route('conversations.index') }}" class="inline-flex items-center gap-1 text-slate-600 transition hover:text-blue-800">
                            Mensajes
                            @if ($unreadMessageCount > 0)
                                <span class="rounded-full bg-blue-800 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $unreadMessageCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('notifications.index') }}" class="inline-flex items-center gap-1 text-slate-600 transition hover:text-blue-800">
                            Notificaciones
                            @if (($unreadCount = auth()->user()->unreadNotifications()->count()) > 0)
                                <span class="rounded-full bg-blue-800 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $unreadCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('dashboard') }}" class="text-slate-600 transition hover:text-blue-800">Mi espacio</a>
                    </div>
                @endif
            @endauth
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
