<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_starts_a_conversation_using_the_listing_seller_not_client_ids(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $listing = Listing::factory()->for($seller)->create();

        $this->actingAs($buyer)
            ->post(route('conversations.start', $listing), [
                'buyer_id' => $seller->id,
                'seller_id' => $buyer->id,
            ])
            ->assertRedirect();

        $conversation = Conversation::query()->sole();
        $this->assertSame($buyer->id, $conversation->buyer_id);
        $this->assertSame($seller->id, $conversation->seller_id);
        $this->assertSame($listing->id, $conversation->listing_id);
    }

    public function test_repeated_or_existing_conversation_starts_are_deduplicated(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $listing = Listing::factory()->for($seller)->create();

        $this->actingAs($buyer)
            ->post(route('conversations.start', $listing))
            ->assertRedirect();
        $conversation = Conversation::query()->sole();
        $this->actingAs($buyer)
            ->post(route('conversations.start', $listing))
            ->assertRedirect(route('conversations.show', $conversation));

        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_existing_conversation_can_be_reopened_after_listing_becomes_unavailable(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $listing = Listing::factory()->for($seller)->create(['status' => Listing::STATUS_SOLD]);
        $conversation = $this->conversation($listing, $buyer, $seller);

        $this->actingAs($buyer)
            ->post(route('conversations.start', $listing))
            ->assertRedirect(route('conversations.show', $conversation));

        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_new_conversations_are_rejected_for_own_inactive_or_unavailable_listings(): void
    {
        $seller = $this->verifiedUser();
        $buyer = $this->verifiedUser();
        $ownListing = Listing::factory()->for($buyer)->create();
        $inactiveListing = Listing::factory()->for($seller)->create(['is_active' => false]);
        $unavailableListing = Listing::factory()->for($seller)->create(['status' => Listing::STATUS_SOLD_OUT_TODAY]);
        $unverifiedSeller = User::factory()->unverified()->create();
        $unverifiedSellerListing = Listing::factory()->for($unverifiedSeller)->create();

        $this->actingAs($buyer)
            ->post(route('conversations.start', $ownListing))
            ->assertForbidden();
        $this->actingAs($buyer)
            ->post(route('conversations.start', $inactiveListing))
            ->assertSessionHasErrors('conversation');
        $this->actingAs($buyer)
            ->post(route('conversations.start', $unavailableListing))
            ->assertSessionHasErrors('conversation');
        $this->actingAs($buyer)
            ->post(route('conversations.start', $unverifiedSellerListing))
            ->assertSessionHasErrors('conversation');

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_guest_and_unverified_user_cannot_start_or_view_conversations(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $listing = Listing::factory()->for($seller)->create();
        $conversation = $this->conversation($listing, $buyer, $seller);

        $this->post(route('conversations.start', $listing))->assertRedirect(route('login'));
        $this->get(route('conversations.index'))->assertRedirect(route('login'));
        $this->get(route('conversations.show', $conversation))->assertRedirect(route('login'));

        $unverified = User::factory()->unverified()->create();
        $this->actingAs($unverified)
            ->post(route('conversations.start', $listing))
            ->assertRedirect(route('verification.notice'));
        $this->actingAs($unverified)
            ->get(route('conversations.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_only_buyer_and_seller_can_view_a_conversation_including_admins(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $listing = Listing::factory()->for($seller)->create();
        $conversation = $this->conversation($listing, $buyer, $seller);
        $outsider = $this->verifiedUser();
        $admin = $this->verifiedUser();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->actingAs($buyer)->get(route('conversations.show', $conversation))->assertOk();
        $this->actingAs($seller)->get(route('conversations.show', $conversation))->assertOk();
        $this->actingAs($outsider)->get(route('conversations.show', $conversation))->assertForbidden();
        $this->actingAs($admin)->get(route('conversations.show', $conversation))->assertForbidden();
    }

    public function test_inbox_includes_buyer_and_seller_conversations_and_orders_by_latest_message(): void
    {
        $student = $this->verifiedUser();
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $buyerListing = Listing::factory()->for($seller)->create(['title' => 'Buyer view product']);
        $sellerListing = Listing::factory()->for($student)->create(['title' => 'Seller view product']);
        $older = $this->conversation($buyerListing, $student, $seller);
        $newer = $this->conversation($sellerListing, $buyer, $student);
        $older->messages()->create([
            'sender_id' => $seller->id,
            'body' => 'Older activity',
            'created_at' => now()->subHour(),
        ]);
        $newer->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Recent activity',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($student)
            ->get(route('conversations.index'))
            ->assertOk()
            ->assertSee('Buyer view product')
            ->assertSee('Seller view product');

        $this->assertLessThan(
            strpos($response->getContent(), 'Buyer view product'),
            strpos($response->getContent(), 'Seller view product'),
        );
    }

    public function test_sending_a_message_trims_body_ignores_forged_ids_and_notifies_only_recipient(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $listing = Listing::factory()->for($seller)->create();
        $conversation = $this->conversation($listing, $buyer, $seller);
        $anotherBuyer = $this->verifiedUser();
        $anotherSeller = $this->verifiedUser();
        $anotherConversation = $this->conversation(
            Listing::factory()->for($anotherSeller)->create(),
            $anotherBuyer,
            $anotherSeller,
        );

        $this->actingAs($buyer)
            ->post(route('conversations.send', $conversation), [
                'body' => '  Meet at the library.  ',
                'sender_id' => $seller->id,
                'conversation_id' => $anotherConversation->id,
            ])
            ->assertRedirect(route('conversations.show', $conversation));

        $message = Message::query()->sole();
        $this->assertSame($conversation->id, $message->conversation_id);
        $this->assertSame($buyer->id, $message->sender_id);
        $this->assertSame('Meet at the library.', $message->body);
        $this->assertNull($message->read_at);
        $this->assertSame(1, $seller->notifications()->count());
        $this->assertSame(0, $buyer->notifications()->count());

        $data = $seller->notifications()->firstOrFail()->data;
        $this->assertSame('new_message', $data['type']);
        $this->assertSame($conversation->id, $data['conversation_id']);
        $this->assertSame($message->id, $data['message_id']);
        $this->assertSame($buyer->name, $data['sender_name']);
        $this->assertSame($message->body, $data['preview']);
    }

    public function test_message_body_is_required_trimmed_and_limited_to_two_thousand_characters(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $conversation = $this->conversation(Listing::factory()->for($seller)->create(), $buyer, $seller);

        foreach (['', '   '] as $body) {
            $this->actingAs($buyer)
                ->post(route('conversations.send', $conversation), ['body' => $body])
                ->assertSessionHasErrors('body');
        }

        $this->actingAs($buyer)
            ->post(route('conversations.send', $conversation), ['body' => str_repeat('a', 2001)])
            ->assertSessionHasErrors('body');

        $this->actingAs($buyer)
            ->post(route('conversations.send', $conversation), ['body' => str_repeat('a', 2000)])
            ->assertRedirect();
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_non_participant_cannot_send_a_message(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $conversation = $this->conversation(Listing::factory()->for($seller)->create(), $buyer, $seller);

        $this->actingAs($this->verifiedUser())
            ->post(route('conversations.send', $conversation), ['body' => 'Unauthorized'])
            ->assertForbidden();

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_opening_chat_marks_only_received_messages_as_read_and_escapes_html(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $conversation = $this->conversation(Listing::factory()->for($seller)->create(), $buyer, $seller);
        $received = $conversation->messages()->create([
            'sender_id' => $seller->id,
            'body' => '<script>alert("unsafe")</script>',
        ]);
        $own = $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'My reply',
        ]);

        $this->actingAs($buyer)
            ->get(route('conversations.show', $conversation))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&quot;unsafe&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false);

        $this->assertNotNull($received->fresh()->read_at);
        $this->assertNull($own->fresh()->read_at);
    }

    public function test_chat_displays_messages_in_chronological_order(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $conversation = $this->conversation(Listing::factory()->for($seller)->create(), $buyer, $seller);
        $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'First message',
            'created_at' => now()->subMinute(),
        ]);
        $conversation->messages()->create([
            'sender_id' => $seller->id,
            'body' => 'Second message',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($buyer)
            ->get(route('conversations.show', $conversation))
            ->assertOk();

        $this->assertLessThan(
            strpos($response->getContent(), 'Second message'),
            strpos($response->getContent(), 'First message'),
        );
    }

    public function test_poll_returns_only_new_messages_for_that_conversation_and_marks_them_read(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $conversation = $this->conversation(Listing::factory()->for($seller)->create(), $buyer, $seller);
        $otherBuyer = $this->verifiedUser();
        $otherSeller = $this->verifiedUser();
        $otherConversation = $this->conversation(
            Listing::factory()->for($otherSeller)->create(),
            $otherBuyer,
            $otherSeller,
        );
        $alreadySeen = $conversation->messages()->create(['sender_id' => $buyer->id, 'body' => 'Seen']);
        $newMessage = $conversation->messages()->create(['sender_id' => $seller->id, 'body' => 'New reply']);
        $otherConversation->messages()->create([
            'sender_id' => $otherConversation->seller_id,
            'body' => 'Other conversation',
        ]);

        $response = $this->actingAs($buyer)
            ->getJson(route('conversations.messages', [$conversation, 'after_id' => $alreadySeen->id]))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.id', $newMessage->id)
            ->assertJsonPath('messages.0.sender_name', $seller->name)
            ->assertJsonPath('messages.0.body', 'New reply');

        $this->assertNotNull($newMessage->fresh()->read_at);
        $this->assertSame([], $response->json('read_receipts'));
    }

    public function test_poll_reflects_read_receipts_for_the_senders_messages(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $conversation = $this->conversation(Listing::factory()->for($seller)->create(), $buyer, $seller);
        $message = $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Read receipt message',
        ]);

        $this->actingAs($seller)
            ->get(route('conversations.show', $conversation))
            ->assertOk();

        $this->actingAs($buyer)
            ->getJson(route('conversations.messages', [$conversation, 'after_id' => $message->id]))
            ->assertOk()
            ->assertJsonPath('read_receipts.0.id', $message->id)
            ->assertJsonPath('read_receipts.0.read_at', $message->fresh()->read_at->toIso8601String());
    }

    public function test_polling_another_users_conversation_is_forbidden(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $conversation = $this->conversation(Listing::factory()->for($seller)->create(), $buyer, $seller);

        $this->actingAs($this->verifiedUser())
            ->getJson(route('conversations.messages', $conversation))
            ->assertForbidden();
    }

    public function test_opening_notifications_can_link_to_the_message_conversation(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $conversation = $this->conversation(Listing::factory()->for($seller)->create(), $buyer, $seller);
        $message = $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Please meet at the student center.',
        ]);
        $seller->notify(new NewMessageNotification($message->load('sender')));

        $this->actingAs($seller)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Nuevo mensaje de '.$buyer->name)
            ->assertSee('Please meet at the student center.')
            ->assertSee(route('conversations.show', $conversation), false);
    }

    public function test_message_sending_is_limited_to_twenty_per_minute_per_user(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $conversation = $this->conversation(Listing::factory()->for($seller)->create(), $buyer, $seller);

        for ($number = 1; $number <= 20; $number++) {
            $this->actingAs($buyer)
                ->post(route('conversations.send', $conversation), ['body' => "Message {$number}"])
                ->assertRedirect();
        }

        $this->actingAs($buyer)
            ->post(route('conversations.send', $conversation), ['body' => 'Rate limit exceeded'])
            ->assertTooManyRequests();

        $this->assertDatabaseCount('messages', 20);
    }

    public function test_navigation_shows_unread_message_count(): void
    {
        $buyer = $this->verifiedUser();
        $seller = $this->verifiedUser();
        $conversation = $this->conversation(Listing::factory()->for($seller)->create(), $buyer, $seller);
        $conversation->messages()->create(['sender_id' => $seller->id, 'body' => 'Unread']);

        $this->actingAs($buyer)
            ->get(route('conversations.index'))
            ->assertOk()
            ->assertSee('Mensajes')
            ->assertSee('1 mensajes sin leer');
    }

    private function verifiedUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    private function conversation(Listing $listing, User $buyer, User $seller): Conversation
    {
        return Conversation::query()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);
    }
}
