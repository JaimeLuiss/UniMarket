@extends('layouts.app')

@section('title', 'Mensajes')

@section('content')
<section class="mx-auto max-w-5xl px-5 py-9 sm:px-8 sm:py-12">
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-blue-700">Comunicación directa</p>
        <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-blue-950 sm:text-4xl">Mensajes</h1>
        <p class="mt-2 text-sm text-slate-500">Coordina tus entregas con estudiantes de tu campus.</p>
    </div>

    @if ($conversations->isEmpty())
        <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <h2 class="font-display text-xl font-bold text-blue-950">Aún no tienes conversaciones</h2>
            <p class="mt-2 text-sm text-slate-500">Contacta a un vendedor desde una publicación para comenzar.</p>
            <a href="{{ route('marketplace.index') }}" class="mt-5 inline-flex rounded-xl bg-blue-800 px-5 py-3 text-sm font-bold text-white hover:bg-blue-900">Explorar marketplace</a>
        </div>
    @else
        <div class="mt-7 space-y-3">
            @foreach ($conversations as $conversation)
                @php
                    $otherParticipant = $conversation->buyer_id === auth()->id()
                        ? $conversation->seller
                        : $conversation->buyer;
                    $lastMessage = $conversation->lastMessage;
                @endphp
                <a href="{{ route('conversations.show', $conversation) }}" class="flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-200 hover:shadow-md sm:p-5">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-50 font-display text-lg font-bold text-blue-800" aria-hidden="true">{{ str($otherParticipant->name)->substr(0, 1)->upper() }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-display font-bold text-blue-950">{{ $otherParticipant->name }}</span>
                            @if ($lastMessage)
                                <time datetime="{{ $lastMessage->created_at->toIso8601String() }}" class="text-xs text-slate-400">{{ $lastMessage->created_at->format('d/m/Y H:i') }}</time>
                            @endif
                        </span>
                        <span class="mt-1 block truncate text-xs font-semibold text-blue-700">{{ $conversation->listing->title }}</span>
                        <span class="mt-2 flex items-center justify-between gap-3">
                            <span class="min-w-0 truncate text-sm text-slate-500">
                                @if ($lastMessage)
                                    {{ $lastMessage->sender_id === auth()->id() ? 'Tú: ' : '' }}{{ $lastMessage->body }}
                                @else
                                    Conversación iniciada. Envía el primer mensaje.
                                @endif
                            </span>
                            @if ($conversation->unread_messages_count > 0)
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-blue-800 text-xs font-bold text-white" aria-label="{{ $conversation->unread_messages_count }} mensajes sin leer">{{ $conversation->unread_messages_count }}</span>
                            @endif
                        </span>
                    </span>
                </a>
            @endforeach
        </div>
        <div class="mt-8">{{ $conversations->links() }}</div>
    @endif
</section>
@endsection
