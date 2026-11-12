@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
<section class="mx-auto max-w-4xl px-5 py-9 sm:px-8 sm:py-12">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-blue-700">Novedades de tu comunidad</p>
            <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-blue-950">Notificaciones</h1>
        </div>
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:border-blue-200 hover:text-blue-800">Marcar todas como leídas</button>
            </form>
        @endif
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    @if ($notifications->isEmpty())
        <div class="mt-7 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <h2 class="font-display text-xl font-bold text-blue-950">No tienes notificaciones</h2>
            <p class="mt-2 text-sm text-slate-500">Aquí aparecerán novedades de los emprendimientos que sigues.</p>
        </div>
    @else
        <div class="mt-7 space-y-3">
            @foreach ($notifications as $notification)
                @php($data = $notification->data)
                <article class="rounded-2xl border p-5 {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-blue-200 bg-blue-50/60' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-blue-950">
                                    @if (($data['type'] ?? '') === 'new_message')
                                        Nuevo mensaje de {{ $data['sender_name'] ?? 'un estudiante' }}
                                    @elseif (($data['type'] ?? '') === \App\Notifications\EntrepreneurUpdateNotification::TYPE_NEW_LISTING)
                                    {{ $data['entrepreneur_name'] }} publicó un producto nuevo
                                @else
                                    {{ $data['entrepreneur_name'] }} volvió a tener productos disponibles
                                @endif
                            </p>
                                @if (($data['type'] ?? '') === 'new_message')
                                    <p class="mt-1 text-sm text-slate-600">{{ $data['preview'] ?? '' }}</p>
                                @elseif (! empty($data['listing_title']))
                                <p class="mt-1 text-sm text-slate-600">{{ $data['listing_title'] }}</p>
                            @endif
                            <p class="mt-2 text-xs text-slate-500">{{ $notification->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        @if (! $notification->read_at)
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-xs font-bold text-blue-800 hover:text-blue-950">Marcar leída</button>
                            </form>
                        @endif
                    </div>
                    <div class="mt-4 flex flex-wrap gap-4 text-sm font-bold">
                        @if (($data['type'] ?? '') === 'new_message' && ! empty($data['conversation_id']))
                            <a href="{{ route('conversations.show', $data['conversation_id']) }}" class="text-blue-800 hover:text-blue-950">Abrir conversación →</a>
                        @elseif (! empty($data['listing_id']))
                            <a href="{{ route('marketplace.show', $data['listing_id']) }}" class="text-blue-800 hover:text-blue-950">Ver publicación →</a>
                        @endif
                        @if (! empty($data['entrepreneur_slug']))
                            <a href="{{ route('entrepreneurs.show', $data['entrepreneur_slug']) }}" class="text-blue-800 hover:text-blue-950">Ver emprendimiento →</a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-8">{{ $notifications->links() }}</div>
    @endif
</section>
@endsection
