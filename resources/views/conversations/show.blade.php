@extends('layouts.app')

@section('title', 'Chat con '.($conversation->buyer_id === auth()->id() ? $conversation->seller->name : $conversation->buyer->name))

@section('content')
@php
    $otherParticipant = $conversation->buyer_id === auth()->id()
        ? $conversation->seller
        : $conversation->buyer;
@endphp
<section class="mx-auto max-w-4xl px-4 py-6 sm:px-8 sm:py-10">
    <a href="{{ route('conversations.index') }}" class="text-sm font-bold text-blue-800 hover:text-blue-950">← Volver a mensajes</a>

    @if (session('status'))
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="mt-5 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex min-w-0 items-center gap-3">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-blue-50 font-display text-lg font-bold text-blue-800" aria-hidden="true">{{ str($otherParticipant->name)->substr(0, 1)->upper() }}</span>
            <div class="min-w-0">
                <h1 class="truncate font-display text-lg font-bold text-blue-950">{{ $otherParticipant->name }}</h1>
                <p class="truncate text-sm text-slate-500">Sobre: {{ $conversation->listing->title }}</p>
            </div>
        </div>
        <a href="{{ route('marketplace.show', $conversation->listing) }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-bold text-blue-800 hover:border-blue-200">Ver publicación</a>
    </div>

    <div
        id="chat-messages"
        data-user-id="{{ auth()->id() }}"
        data-last-id="{{ $messages->last()?->id ?? 0 }}"
        data-messages-url="{{ route('conversations.messages', $conversation) }}"
        class="mt-4 flex max-h-[60vh] min-h-80 flex-col gap-3 overflow-y-auto rounded-2xl border border-slate-200 bg-slate-100/70 p-4 sm:p-6"
        role="log"
        aria-live="polite"
        aria-label="Historial de mensajes"
    >
        @forelse ($messages as $message)
            @php($isOwnMessage = $message->sender_id === auth()->id())
            <article data-message-id="{{ $message->id }}" class="flex {{ $isOwnMessage ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[88%] rounded-2xl px-4 py-3 shadow-sm sm:max-w-[75%] {{ $isOwnMessage ? 'rounded-br-md bg-blue-800 text-white' : 'rounded-bl-md border border-slate-200 bg-white text-slate-800' }}">
                    @unless ($isOwnMessage)
                        <p class="mb-1 text-xs font-bold text-blue-800">{{ $message->sender->name }}</p>
                    @endunless
                    <p class="whitespace-pre-wrap break-words text-sm leading-6">{{ $message->body }}</p>
                    <div class="mt-2 flex items-center justify-end gap-2 text-[11px] {{ $isOwnMessage ? 'text-blue-100' : 'text-slate-400' }}">
                        <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('H:i') }}</time>
                        @if ($isOwnMessage)
                            <span data-read-status>{{ $message->read_at ? 'Leído' : 'Enviado' }}</span>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <p id="chat-empty-state" class="m-auto max-w-sm text-center text-sm text-slate-500">Aún no hay mensajes. Escribe para iniciar la conversación.</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('conversations.send', $conversation) }}" class="mt-4 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        @csrf
        <label for="body" class="sr-only">Escribe tu mensaje</label>
        <div class="flex items-end gap-3">
            <textarea id="body" name="body" rows="2" maxlength="2000" required placeholder="Escribe un mensaje..." class="form-input max-h-40 min-h-12 flex-1 resize-y">{{ old('body') }}</textarea>
            <button type="submit" class="shrink-0 rounded-xl bg-blue-800 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-900">Enviar</button>
        </div>
        <p class="mt-2 text-right text-xs text-slate-400">Máximo 2.000 caracteres</p>
    </form>
</section>

<script>
    (() => {
        const chat = document.getElementById('chat-messages');

        if (!chat) {
            return;
        }

        const currentUserId = Number(chat.dataset.userId);
        const messagesUrl = chat.dataset.messagesUrl;
        const seenIds = new Set(
            Array.from(chat.querySelectorAll('[data-message-id]'), (element) => Number(element.dataset.messageId)),
        );
        let lastId = Number(chat.dataset.lastId);
        let activeRequest = null;
        let intervalId = null;

        function appendMessage(message) {
            const ownMessage = Number(message.sender_id) === currentUserId;
            const article = document.createElement('article');
            article.dataset.messageId = String(message.id);
            article.className = ownMessage ? 'flex justify-end' : 'flex justify-start';

            const bubble = document.createElement('div');
            bubble.className = ownMessage
                ? 'max-w-[88%] rounded-2xl rounded-br-md bg-blue-800 px-4 py-3 text-white shadow-sm sm:max-w-[75%]'
                : 'max-w-[88%] rounded-2xl rounded-bl-md border border-slate-200 bg-white px-4 py-3 text-slate-800 shadow-sm sm:max-w-[75%]';

            if (!ownMessage) {
                const sender = document.createElement('p');
                sender.className = 'mb-1 text-xs font-bold text-blue-800';
                sender.textContent = message.sender_name;
                bubble.append(sender);
            }

            const body = document.createElement('p');
            body.className = 'whitespace-pre-wrap break-words text-sm leading-6';
            body.textContent = message.body;
            bubble.append(body);

            const meta = document.createElement('div');
            meta.className = ownMessage
                ? 'mt-2 flex items-center justify-end gap-2 text-[11px] text-blue-100'
                : 'mt-2 flex items-center justify-end gap-2 text-[11px] text-slate-400';
            const time = document.createElement('time');
            time.dateTime = message.created_at;
            time.textContent = new Date(message.created_at).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
            meta.append(time);

            if (ownMessage) {
                const readStatus = document.createElement('span');
                readStatus.dataset.readStatus = '';
                readStatus.textContent = message.read_at ? 'Leído' : 'Enviado';
                meta.append(readStatus);
            }

            bubble.append(meta);
            article.append(bubble);
            document.getElementById('chat-empty-state')?.remove();
            chat.append(article);
            seenIds.add(Number(message.id));
            lastId = Math.max(lastId, Number(message.id));
        }

        function updateReadReceipts(receipts) {
            for (const receipt of receipts) {
                const article = chat.querySelector(`[data-message-id="${Number(receipt.id)}"]`);
                const status = article?.querySelector('[data-read-status]');

                if (status) {
                    status.textContent = 'Leído';
                }
            }
        }

        async function pollMessages() {
            if (document.hidden || !chat.isConnected || activeRequest) {
                return;
            }

            activeRequest = new AbortController();

            try {
                const response = await fetch(`${messagesUrl}?after_id=${lastId}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    signal: activeRequest.signal,
                });

                if (!response.ok) {
                    if ([401, 403, 404].includes(response.status)) {
                        window.clearInterval(intervalId);
                    }

                    return;
                }

                const payload = await response.json();
                for (const message of payload.messages) {
                    if (!seenIds.has(Number(message.id))) {
                        appendMessage(message);
                    }
                }
                updateReadReceipts(payload.read_receipts);
                chat.scrollTop = chat.scrollHeight;
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('No se pudieron actualizar los mensajes.', error);
                }
            } finally {
                activeRequest = null;
            }
        }

        chat.scrollTop = chat.scrollHeight;
        intervalId = window.setInterval(pollMessages, 5000);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                pollMessages();
            }
        });
        window.addEventListener('pagehide', () => {
            window.clearInterval(intervalId);
            activeRequest?.abort();
        }, { once: true });
    })();
</script>
@endsection
