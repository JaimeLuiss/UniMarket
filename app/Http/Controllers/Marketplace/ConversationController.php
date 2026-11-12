<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $conversations = Conversation::query()
            ->where(fn (Builder $query) => $query
                ->where('buyer_id', $user->id)
                ->orWhere('seller_id', $user->id))
            ->with([
                'buyer:id,name',
                'seller:id,name',
                'listing:id,title',
                'lastMessage.sender:id,name',
            ])
            ->withCount([
                'messages as unread_messages_count' => fn (Builder $query) => $query
                    ->whereNull('read_at')
                    ->where('sender_id', '!=', $user->id),
            ])
            ->withMax('messages', 'created_at')
            ->orderByRaw('COALESCE(messages_max_created_at, conversations.updated_at) DESC')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('conversations.index', compact('conversations'));
    }

    public function start(Request $request, Listing $listing): RedirectResponse
    {
        $buyer = $request->user();
        abort_unless($buyer instanceof User, 403);

        $conversation = DB::transaction(function () use ($buyer, $listing): Conversation {
            $currentListing = Listing::query()
                ->with(['user', 'category', 'entrepreneurProfile'])
                ->lockForUpdate()
                ->findOrFail($listing->id);

            abort_if($currentListing->user_id === $buyer->id, 403, 'No puedes contactar tu propia publicación.');

            $existing = Conversation::query()
                ->where('listing_id', $currentListing->id)
                ->where('buyer_id', $buyer->id)
                ->where('seller_id', $currentListing->user_id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $isPublicAndAvailable = $currentListing->isAvailable()
                && $currentListing->published_at !== null
                && $currentListing->user->email_verified_at !== null
                && $currentListing->category->is_active
                && ($currentListing->entrepreneurProfile === null || $currentListing->entrepreneurProfile->is_active);

            if (! $isPublicAndAvailable) {
                throw ValidationException::withMessages([
                    'conversation' => 'No puedes iniciar una conversación con una publicación inactiva o no disponible.',
                ]);
            }

            return Conversation::query()->firstOrCreate([
                'listing_id' => $currentListing->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $currentListing->user_id,
            ]);
        });

        return redirect()->route('conversations.show', $conversation);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        Gate::authorize('view', $conversation);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->update(['read_at' => now(), 'updated_at' => now()]);

        $conversation->load(['buyer:id,name', 'seller:id,name', 'listing']);
        $messages = $conversation->messages()->with('sender:id,name')->orderBy('id')->get();

        return view('conversations.show', compact('conversation', 'messages'));
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);
        $afterId = (int) ($validated['after_id'] ?? 0);

        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->update(['read_at' => now(), 'updated_at' => now()]);

        $messages = $conversation->messages()
            ->where('id', '>', $afterId)
            ->with('sender:id,name')
            ->orderBy('id')
            ->get()
            ->map(fn (Message $message): array => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender->name,
                'body' => $message->body,
                'created_at' => $message->created_at->toIso8601String(),
                'read_at' => $message->read_at?->toIso8601String(),
            ]);

        $readReceipts = $conversation->messages()
            ->where('sender_id', $user->id)
            ->whereNotNull('read_at')
            ->get(['id', 'read_at'])
            ->map(fn (Message $message): array => [
                'id' => $message->id,
                'read_at' => $message->read_at?->toIso8601String(),
            ]);

        return response()->json([
            'messages' => $messages,
            'read_receipts' => $readReceipts,
        ]);
    }

    public function send(Request $request, Conversation $conversation): RedirectResponse
    {
        Gate::authorize('sendMessage', $conversation);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $body = $request->input('body');
        if (is_string($body)) {
            $request->merge(['body' => trim($body)]);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $recipient = $conversation->buyer_id === $user->id
            ? $conversation->seller
            : $conversation->buyer;

        DB::transaction(function () use ($conversation, $recipient, $user, $validated): void {
            $message = $conversation->messages()->create([
                'sender_id' => $user->id,
                'body' => $validated['body'],
                'read_at' => null,
            ]);
            $message->setRelation('sender', $user);

            $conversation->touch();
            $recipient->notify(new NewMessageNotification($message));
        });

        return redirect()->route('conversations.show', $conversation)
            ->with('status', 'Tu mensaje fue enviado.');
    }
}
