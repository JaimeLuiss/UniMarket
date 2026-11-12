<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\EntrepreneurProfile;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Message;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MarketplaceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_migrations_create_the_required_tables_and_columns(): void
    {
        foreach ([
            'users',
            'categories',
            'entrepreneur_profiles',
            'listings',
            'listing_images',
            'favorites',
            'entrepreneur_favorites',
            'conversations',
            'messages',
            'reports',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('users', ['role', 'phone']));
        $this->assertTrue(Schema::hasColumns('listings', [
            'listing_type',
            'availability_type',
            'status',
            'price',
            'published_at',
        ]));
        $this->assertTrue(Schema::hasIndex('listings', ['entrepreneur_profile_id']));
        $this->assertTrue(Schema::hasIndex('reports', ['reviewed_by']));
        $this->assertSame(1, (int) DB::scalar('PRAGMA foreign_keys'));
    }

    public function test_category_seeder_creates_exactly_the_four_marketplace_categories(): void
    {
        Artisan::call('db:seed', ['--class' => CategorySeeder::class]);
        Artisan::call('db:seed', ['--class' => CategorySeeder::class]);

        $this->assertSame([
            'Alimentos y Snacks',
            'Tecnología y Artículos Personales',
            'Material Académico y Laboratorio',
            'Emprendimientos y Otros',
        ], Category::query()->orderBy('sort_order')->pluck('name')->all());
    }

    public function test_user_can_have_multiple_listings(): void
    {
        $user = User::factory()->create();
        Listing::factory()->count(2)->for($user)->create();

        $this->assertCount(2, $user->listings);
    }

    public function test_listing_belongs_to_a_category(): void
    {
        $category = Category::factory()->create();
        $listing = Listing::factory()->for($category)->create();

        $this->assertTrue($listing->category->is($category));
    }

    public function test_user_can_create_an_entrepreneur_profile(): void
    {
        $user = User::factory()->create();
        $profile = EntrepreneurProfile::factory()->for($user)->create();

        $this->assertTrue($user->entrepreneurProfile->is($profile));
        $this->assertTrue($profile->user->is($user));
    }

    public function test_listing_can_have_multiple_images(): void
    {
        $listing = Listing::factory()->create();
        $listing->images()->createMany([
            ['path' => 'listings/one.jpg'],
            ['path' => 'listings/two.jpg'],
        ]);

        $this->assertCount(2, $listing->images);
        $this->assertInstanceOf(ListingImage::class, $listing->images->first());
    }

    public function test_user_can_favorite_a_listing_and_duplicates_are_rejected(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create();
        $user->favorites()->attach($listing);

        $this->assertTrue($user->favorites->first()->is($listing));
        $this->assertTrue($listing->favoritedBy->first()->is($user));
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'listing_id' => $listing->id]);

        $this->expectException(QueryException::class);
        $user->favorites()->attach($listing);
    }

    public function test_user_can_follow_an_entrepreneur_and_duplicates_are_rejected(): void
    {
        $user = User::factory()->create();
        $profile = EntrepreneurProfile::factory()->create();
        $user->favoriteEntrepreneurs()->attach($profile);

        $this->assertTrue($user->favoriteEntrepreneurs->first()->is($profile));
        $this->assertTrue($profile->followers->first()->is($user));
        $this->assertDatabaseHas('entrepreneur_favorites', [
            'user_id' => $user->id,
            'entrepreneur_profile_id' => $profile->id,
        ]);

        $this->expectException(QueryException::class);
        $user->favoriteEntrepreneurs()->attach($profile);
    }

    public function test_conversation_relates_buyer_seller_and_listing_and_prevents_duplicates(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $listing = Listing::factory()->for($seller)->create();
        $conversation = Conversation::query()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $this->assertTrue($conversation->buyer->is($buyer));
        $this->assertTrue($conversation->seller->is($seller));
        $this->assertTrue($conversation->listing->is($listing));
        $this->assertTrue($buyer->conversationsAsBuyer->first()->is($conversation));
        $this->assertTrue($seller->conversationsAsSeller->first()->is($conversation));

        $this->expectException(QueryException::class);
        Conversation::query()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);
    }

    public function test_messages_belong_to_their_conversation_and_sender(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $conversation = Conversation::query()->create([
            'listing_id' => Listing::factory()->for($seller)->create()->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);
        $message = $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Is this still available?',
        ]);

        $this->assertTrue($message->conversation->is($conversation));
        $this->assertTrue($message->sender->is($buyer));
        $this->assertTrue($conversation->messages->first()->is($message));
    }

    public function test_report_records_the_reporting_user_and_related_entities(): void
    {
        $reporter = User::factory()->create();
        $reportedUser = User::factory()->create();
        $listing = Listing::factory()->create();
        $report = $reporter->reports()->create([
            'listing_id' => $listing->id,
            'reported_user_id' => $reportedUser->id,
            'reason' => 'inappropriate_content',
            'description' => 'This listing violates the marketplace rules.',
        ]);

        $this->assertTrue($report->reporter->is($reporter));
        $this->assertTrue($report->reportedUser->is($reportedUser));
        $this->assertTrue($report->listing->is($listing));
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => Report::STATUS_PENDING,
        ]);
    }
}
