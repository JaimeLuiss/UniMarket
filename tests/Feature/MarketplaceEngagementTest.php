<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\EntrepreneurProfile;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\EntrepreneurUpdateNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketplaceEngagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_user_can_add_and_remove_a_listing_favorite_without_duplicates(): void
    {
        $user = $this->verifiedUser();
        $listing = Listing::factory()->create();

        $this->actingAs($user)->post(route('marketplace.favorite', $listing))->assertRedirect();
        $this->actingAs($user)->post(route('marketplace.favorite', $listing))->assertRedirect();
        $this->assertDatabaseCount('favorites', 1);

        $this->actingAs($user)->delete(route('marketplace.unfavorite', $listing))->assertRedirect();
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'listing_id' => $listing->id]);
    }

    public function test_users_cannot_favorite_their_own_or_unavailable_listings(): void
    {
        $owner = $this->verifiedUser();
        $own = Listing::factory()->for($owner)->create();
        $inactive = Listing::factory()->create(['is_active' => false]);
        $soldOut = Listing::factory()->create(['status' => Listing::STATUS_SOLD_OUT_TODAY]);
        $emptyStock = Listing::factory()->create(['stock' => 0]);
        $user = $this->verifiedUser();

        $this->actingAs($owner)
            ->post(route('marketplace.favorite', $own))
            ->assertSessionHasErrors('favorite');
        $this->actingAs($user)
            ->post(route('marketplace.favorite', $inactive))
            ->assertSessionHasErrors('favorite');
        $this->actingAs($user)
            ->post(route('marketplace.favorite', $soldOut))
            ->assertSessionHasErrors('favorite');
        $this->actingAs($user)
            ->post(route('marketplace.favorite', $emptyStock))
            ->assertSessionHasErrors('favorite');
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_favorites_page_lists_only_the_authenticated_users_saved_publications(): void
    {
        $user = $this->verifiedUser();
        $mine = Listing::factory()->create(['title' => 'My saved product']);
        $other = Listing::factory()->create(['title' => 'Other users saved product']);
        $user->favorites()->attach($mine);
        $this->verifiedUser()->favorites()->attach($other);

        $this->actingAs($user)
            ->get(route('my-favorites'))
            ->assertOk()
            ->assertSee($mine->title)
            ->assertDontSee($other->title);
    }

    public function test_user_can_create_only_one_profile_and_slug_is_generated_uniquely(): void
    {
        $user = $this->verifiedUser();
        EntrepreneurProfile::factory()->create(['business_name' => 'Blue Campus', 'slug' => 'blue-campus']);

        $this->actingAs($user)
            ->post(route('my-entrepreneur.store'), $this->profileData(['business_name' => 'Blue Campus']))
            ->assertRedirect(route('my-entrepreneur'));

        $this->assertDatabaseHas('entrepreneur_profiles', [
            'user_id' => $user->id,
            'business_name' => 'Blue Campus',
            'slug' => 'blue-campus-2',
        ]);

        $this->actingAs($user)
            ->post(route('my-entrepreneur.store'), $this->profileData(['business_name' => 'Another profile']))
            ->assertRedirect(route('my-entrepreneur.edit'));
        $this->assertDatabaseCount('entrepreneur_profiles', 2);
    }

    public function test_user_can_edit_own_profile_but_cannot_edit_another_users_profile(): void
    {
        $owner = $this->verifiedUser();
        $profile = EntrepreneurProfile::factory()->for($owner)->create();

        $this->actingAs($owner)
            ->put(route('my-entrepreneur.update'), $this->profileData(['business_name' => 'Updated business']))
            ->assertRedirect(route('my-entrepreneur'));
        $this->assertSame('Updated business', $profile->fresh()->business_name);

        $other = $this->verifiedUser();
        $this->actingAs($other)
            ->put(route('my-entrepreneur.update'), $this->profileData(['business_name' => 'Unauthorized change']))
            ->assertRedirect(route('my-entrepreneur.create'));
        $this->assertSame('Updated business', $profile->fresh()->business_name);
    }

    public function test_owner_can_activate_and_deactivate_their_profile(): void
    {
        $owner = $this->verifiedUser();
        $profile = EntrepreneurProfile::factory()->for($owner)->create();

        $this->actingAs($owner)
            ->patch(route('my-entrepreneur.status'), ['is_active' => 0])
            ->assertRedirect();
        $this->assertFalse($profile->fresh()->is_active);

        $this->actingAs($owner)
            ->patch(route('my-entrepreneur.status'), ['is_active' => 1])
            ->assertRedirect();
        $this->assertTrue($profile->fresh()->is_active);
    }

    public function test_owner_can_upload_and_replace_their_profile_logo(): void
    {
        $owner = $this->verifiedUser();

        $this->actingAs($owner)
            ->post(route('my-entrepreneur.store'), $this->profileData([
                'logo' => $this->fakePng('first.png'),
            ]))
            ->assertRedirect(route('my-entrepreneur'));
        $profile = $owner->fresh()->entrepreneurProfile;
        $firstLogo = $profile->logo_path;
        Storage::disk('public')->assertExists($firstLogo);

        $this->actingAs($owner)
            ->put(route('my-entrepreneur.update'), $this->profileData([
                'business_name' => $profile->business_name,
                'logo' => $this->fakePng('second.png'),
            ]))
            ->assertRedirect(route('my-entrepreneur'));

        $this->assertNotSame($firstLogo, $profile->fresh()->logo_path);
        Storage::disk('public')->assertMissing($firstLogo);
        Storage::disk('public')->assertExists($profile->fresh()->logo_path);
    }

    public function test_listing_can_only_be_associated_with_owners_active_profile(): void
    {
        $owner = $this->verifiedUser();
        $otherProfile = EntrepreneurProfile::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($owner)
            ->post(route('marketplace.store'), $this->listingData($category, [
                'entrepreneur_profile_id' => $otherProfile->id,
            ]))
            ->assertSessionHasErrors('entrepreneur_profile_id');

        $ownProfile = EntrepreneurProfile::factory()->for($owner)->create(['is_active' => false]);
        $this->actingAs($owner)
            ->post(route('marketplace.store'), $this->listingData($category, [
                'entrepreneur_profile_id' => $ownProfile->id,
            ]))
            ->assertSessionHasErrors('entrepreneur_profile_id');
        $this->assertDatabaseCount('listings', 0);
    }

    public function test_public_active_entrepreneur_page_shows_products_without_owner_name(): void
    {
        $owner = $this->verifiedUser(['name' => 'Private Owner Name']);
        $profile = EntrepreneurProfile::factory()->for($owner)->create([
            'business_name' => 'Campus Bakery',
            'description' => 'Fresh pastries for students.',
            'campus_location' => 'Student center',
        ]);
        $listing = Listing::factory()->for($owner)->create([
            'entrepreneur_profile_id' => $profile->id,
            'title' => 'Morning pastry',
        ]);

        $this->actingAs($this->verifiedUser())
            ->get(route('entrepreneurs.show', $profile->slug))
            ->assertOk()
            ->assertSee($profile->business_name)
            ->assertSee($profile->description)
            ->assertSee($profile->campus_location)
            ->assertSee($listing->title)
            ->assertDontSee('Private Owner Name');

        $profile->update(['is_active' => false]);
        $this->actingAs($this->verifiedUser())
            ->get(route('entrepreneurs.show', $profile->slug))
            ->assertNotFound();
    }

    public function test_inactive_entrepreneur_products_are_not_public_in_the_marketplace(): void
    {
        $owner = $this->verifiedUser();
        $profile = EntrepreneurProfile::factory()->for($owner)->create(['is_active' => false]);
        $listing = Listing::factory()->for($owner)->create([
            'entrepreneur_profile_id' => $profile->id,
            'title' => 'Hidden inactive business product',
        ]);

        $this->actingAs($this->verifiedUser())
            ->get(route('marketplace.index'))
            ->assertOk()
            ->assertDontSee($listing->title);
    }

    public function test_user_can_follow_and_unfollow_an_active_entrepreneur_once(): void
    {
        $profile = EntrepreneurProfile::factory()->create();
        $user = $this->verifiedUser();

        $this->actingAs($user)->post(route('entrepreneurs.follow', $profile))->assertRedirect();
        $this->actingAs($user)->post(route('entrepreneurs.follow', $profile))->assertRedirect();
        $this->assertDatabaseCount('entrepreneur_favorites', 1);

        $this->actingAs($user)->delete(route('entrepreneurs.unfollow', $profile))->assertRedirect();
        $this->assertDatabaseMissing('entrepreneur_favorites', [
            'user_id' => $user->id,
            'entrepreneur_profile_id' => $profile->id,
        ]);
    }

    public function test_user_cannot_follow_their_own_or_an_inactive_entrepreneur(): void
    {
        $owner = $this->verifiedUser();
        $ownProfile = EntrepreneurProfile::factory()->for($owner)->create();
        $inactiveProfile = EntrepreneurProfile::factory()->create(['is_active' => false]);

        $this->actingAs($owner)
            ->post(route('entrepreneurs.follow', $ownProfile))
            ->assertSessionHasErrors('follow');
        $this->actingAs($this->verifiedUser())
            ->post(route('entrepreneurs.follow', $inactiveProfile))
            ->assertSessionHasErrors('follow');
        $this->assertDatabaseCount('entrepreneur_favorites', 0);
    }

    public function test_following_page_shows_only_the_authenticated_users_profiles(): void
    {
        $user = $this->verifiedUser();
        $mine = EntrepreneurProfile::factory()->create(['business_name' => 'Followed business']);
        $other = EntrepreneurProfile::factory()->create(['business_name' => 'Someone elses business']);
        $user->favoriteEntrepreneurs()->attach($mine);
        $this->verifiedUser()->favoriteEntrepreneurs()->attach($other);

        $this->actingAs($user)
            ->get(route('entrepreneurs.following'))
            ->assertOk()
            ->assertSee($mine->business_name)
            ->assertDontSee($other->business_name);
    }

    public function test_followers_are_notified_when_a_followed_entrepreneur_publishes(): void
    {
        $owner = $this->verifiedUser();
        $follower = $this->verifiedUser();
        $secondFollower = $this->verifiedUser();
        $profile = EntrepreneurProfile::factory()->for($owner)->create();
        $profile->followers()->attach([$owner->id, $follower->id, $secondFollower->id]);
        $category = Category::factory()->create();

        $this->actingAs($owner)
            ->post(route('marketplace.store'), $this->listingData($category, [
                'entrepreneur_profile_id' => $profile->id,
            ]))
            ->assertRedirect();

        $this->assertDatabaseCount('notifications', 2);
        $this->assertSame(1, $follower->notifications()->count());
        $this->assertSame(1, $secondFollower->notifications()->count());
        $this->assertSame(0, $owner->notifications()->count());
        $this->assertSame(
            EntrepreneurUpdateNotification::TYPE_NEW_LISTING,
            $follower->notifications()->first()->data['type']
        );
    }

    public function test_followers_are_notified_when_sold_out_daily_stock_returns(): void
    {
        $owner = $this->verifiedUser();
        $follower = $this->verifiedUser();
        $profile = EntrepreneurProfile::factory()->for($owner)->create();
        $profile->followers()->attach($follower);
        $listing = Listing::factory()->for($owner)->create([
            'entrepreneur_profile_id' => $profile->id,
            'availability_type' => Listing::AVAILABILITY_DAILY,
            'status' => Listing::STATUS_SOLD_OUT_TODAY,
            'stock' => 0,
        ]);

        $this->actingAs($owner)
            ->patch(route('marketplace.availability', $listing), [
                'status' => Listing::STATUS_AVAILABLE,
                'stock' => 4,
            ])
            ->assertRedirect();

        $this->assertSame(1, $follower->notifications()->count());
        $this->assertSame(
            EntrepreneurUpdateNotification::TYPE_RESTOCKED,
            $follower->notifications()->first()->data['type']
        );
    }

    public function test_inactive_entrepreneur_followers_do_not_receive_availability_notifications(): void
    {
        $owner = $this->verifiedUser();
        $follower = $this->verifiedUser();
        $profile = EntrepreneurProfile::factory()->for($owner)->create(['is_active' => false]);
        $profile->followers()->attach($follower);
        $listing = Listing::factory()->for($owner)->create([
            'entrepreneur_profile_id' => $profile->id,
            'status' => Listing::STATUS_SOLD_OUT_TODAY,
            'stock' => 0,
        ]);

        $this->actingAs($owner)
            ->patch(route('marketplace.availability', $listing), [
                'status' => Listing::STATUS_AVAILABLE,
                'stock' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_user_can_mark_their_notification_read_but_not_another_users(): void
    {
        $owner = $this->verifiedUser();
        $profile = EntrepreneurProfile::factory()->for($owner)->create();
        $listing = Listing::factory()->for($owner)->create();
        $notification = new EntrepreneurUpdateNotification(
            $profile,
            $listing,
            EntrepreneurUpdateNotification::TYPE_NEW_LISTING,
        );
        $user = $this->verifiedUser();
        $anotherUser = $this->verifiedUser();
        $user->notify($notification);
        $anotherUser->notify($notification);
        $userNotification = $user->notifications()->firstOrFail();
        $otherNotification = $anotherUser->notifications()->firstOrFail();

        $this->actingAs($user)
            ->patch(route('notifications.read', $userNotification->id))
            ->assertRedirect();
        $this->assertNotNull($userNotification->fresh()->read_at);

        $this->actingAs($user)
            ->patch(route('notifications.read', $otherNotification->id))
            ->assertNotFound();
    }

    public function test_notifications_page_is_private_and_mark_all_read_updates_only_own_records(): void
    {
        $owner = $this->verifiedUser();
        $profile = EntrepreneurProfile::factory()->for($owner)->create();
        $user = $this->verifiedUser();
        $anotherUser = $this->verifiedUser();
        $user->notify(new EntrepreneurUpdateNotification(
            $profile,
            Listing::factory()->create(['title' => 'My notification product']),
            EntrepreneurUpdateNotification::TYPE_NEW_LISTING,
        ));
        $anotherUser->notify(new EntrepreneurUpdateNotification(
            $profile,
            Listing::factory()->create(['title' => 'Private notification product']),
            EntrepreneurUpdateNotification::TYPE_NEW_LISTING,
        ));

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('My notification product')
            ->assertDontSee('Private notification product');

        $this->actingAs($user)->patch(route('notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
        $this->assertSame(1, $anotherUser->fresh()->unreadNotifications()->count());
    }

    private function verifiedUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    private function profileData(array $overrides = []): array
    {
        return array_merge([
            'business_name' => 'My Campus Business',
            'description' => 'A student business for the campus community.',
            'campus_location' => 'Library',
            'is_active' => 1,
        ], $overrides);
    }

    private function listingData(Category $category, array $overrides = []): array
    {
        return array_merge([
            'title' => 'New campus listing',
            'description' => 'A useful item for students.',
            'category_id' => $category->id,
            'listing_type' => Listing::TYPE_SALE,
            'price' => '12.50',
            'availability_type' => Listing::AVAILABILITY_DAILY,
            'condition' => Listing::CONDITION_NEW,
            'stock' => '',
            'campus_location' => 'Student center',
        ], $overrides);
    }

    private function fakePng(string $name): UploadedFile
    {
        $contents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l2cAAAAASUVORK5CYII=',
            true,
        );

        return UploadedFile::fake()->createWithContent($name, $contents);
    }
}
