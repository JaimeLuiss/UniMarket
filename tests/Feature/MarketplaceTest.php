<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_verified_user_can_access_marketplace(): void
    {
        $this->actingAs($this->verifiedUser())
            ->get(route('marketplace.index'))
            ->assertOk()
            ->assertSee('Marketplace');
    }

    public function test_guest_cannot_access_marketplace(): void
    {
        $this->get(route('marketplace.index'))->assertRedirect(route('login'));
    }

    public function test_unverified_user_cannot_access_marketplace(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('marketplace.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_can_open_create_and_edit_forms(): void
    {
        $owner = $this->verifiedUser();
        $listing = $this->listing($owner);

        $this->actingAs($owner)
            ->get(route('marketplace.create'))
            ->assertOk()
            ->assertSee('Crear publicación');

        $this->actingAs($owner)
            ->get(route('marketplace.edit', $listing))
            ->assertOk()
            ->assertSee($listing->title);
    }

    public function test_catalog_shows_only_active_published_listings_from_verified_users(): void
    {
        $owner = $this->verifiedUser();
        $active = $this->listing($owner, ['title' => 'Visible campus item']);
        $this->listing($owner, ['title' => 'Disabled item', 'is_active' => false]);
        $this->listing($owner, ['title' => 'Unpublished item', 'published_at' => null]);
        $this->listing(User::factory()->unverified()->create(), ['title' => 'Unverified seller item']);

        $this->actingAs($this->verifiedUser())
            ->get(route('marketplace.index'))
            ->assertOk()
            ->assertSee($active->title)
            ->assertDontSee('Disabled item')
            ->assertDontSee('Unpublished item')
            ->assertDontSee('Unverified seller item');
    }

    public function test_catalog_searches_listing_title(): void
    {
        $owner = $this->verifiedUser();
        $match = $this->listing($owner, ['title' => 'Organic campus coffee']);
        $this->listing($owner, ['title' => 'Notebook']);

        $this->actingAs($this->verifiedUser())
            ->get(route('marketplace.index', ['search' => 'Organic']))
            ->assertOk()
            ->assertSee($match->title)
            ->assertDontSee('Notebook');
    }

    public function test_catalog_searches_listing_description(): void
    {
        $owner = $this->verifiedUser();
        $match = $this->listing($owner, [
            'title' => 'Handmade snack',
            'description' => 'Fresh cinnamon rolls baked each morning.',
        ]);
        $this->listing($owner, ['description' => 'A used physics textbook.']);

        $this->actingAs($this->verifiedUser())
            ->get(route('marketplace.index', ['search' => 'cinnamon']))
            ->assertOk()
            ->assertSee($match->title)
            ->assertDontSee('A used physics textbook.');
    }

    public function test_catalog_filters_by_category(): void
    {
        $owner = $this->verifiedUser();
        $category = Category::factory()->create(['name' => 'Technology']);
        $match = $this->listing($owner, ['category_id' => $category->id, 'title' => 'Camera']);
        $this->listing($owner, ['title' => 'Sandwich']);

        $this->actingAs($this->verifiedUser())
            ->get(route('marketplace.index', ['category' => $category->id]))
            ->assertOk()
            ->assertSee($match->title)
            ->assertDontSee('Sandwich');
    }

    public function test_catalog_filters_by_status(): void
    {
        $owner = $this->verifiedUser();
        $soldOut = $this->listing($owner, [
            'title' => 'Sold out today',
            'status' => Listing::STATUS_SOLD_OUT_TODAY,
        ]);
        $this->listing($owner, [
            'title' => 'Still available',
            'status' => Listing::STATUS_AVAILABLE,
        ]);

        $this->actingAs($this->verifiedUser())
            ->get(route('marketplace.index', ['status' => Listing::STATUS_SOLD_OUT_TODAY]))
            ->assertOk()
            ->assertSee($soldOut->title)
            ->assertDontSee('Still available');
    }

    public function test_catalog_combines_search_category_type_and_availability_filters(): void
    {
        $owner = $this->verifiedUser();
        $category = Category::factory()->create();
        $match = $this->listing($owner, [
            'category_id' => $category->id,
            'title' => 'Campus calculator',
            'listing_type' => Listing::TYPE_EXCHANGE,
            'price' => null,
            'availability_type' => Listing::AVAILABILITY_UNIQUE,
        ]);
        $this->listing($owner, [
            'category_id' => $category->id,
            'title' => 'Campus calculator daily',
            'availability_type' => Listing::AVAILABILITY_DAILY,
        ]);
        $this->listing($owner, [
            'title' => 'Different exchange',
            'listing_type' => Listing::TYPE_EXCHANGE,
            'price' => null,
            'availability_type' => Listing::AVAILABILITY_UNIQUE,
        ]);

        $this->actingAs($this->verifiedUser())
            ->get(route('marketplace.index', [
                'search' => 'calculator',
                'category' => $category->id,
                'type' => Listing::TYPE_EXCHANGE,
                'availability' => Listing::AVAILABILITY_UNIQUE,
            ]))
            ->assertOk()
            ->assertSee($match->title)
            ->assertDontSee('Campus calculator daily')
            ->assertDontSee('Different exchange');
    }

    public function test_listing_detail_shows_product_seller_and_listing_information(): void
    {
        $owner = $this->verifiedUser(['name' => 'Seller Taylor']);
        $category = Category::factory()->create(['name' => 'Books']);
        $listing = $this->listing($owner, [
            'category_id' => $category->id,
            'title' => 'Calculus textbook',
            'description' => 'Complete description of the textbook.',
            'condition' => Listing::CONDITION_USED,
            'campus_location' => 'Main library',
        ]);

        $this->actingAs($this->verifiedUser())
            ->get(route('marketplace.show', $listing))
            ->assertOk()
            ->assertSee('Calculus textbook')
            ->assertSee('Complete description of the textbook.')
            ->assertSee('Books')
            ->assertSee('Seller Taylor')
            ->assertSee('Main library')
            ->assertSee('Usado');
    }

    public function test_price_sort_orders_sale_listings_and_omits_exchange_prices(): void
    {
        $owner = $this->verifiedUser();
        $this->listing($owner, ['title' => 'Higher priced item', 'price' => 80]);
        $this->listing($owner, ['title' => 'Lower priced item', 'price' => 10]);
        $this->listing($owner, [
            'title' => 'Price-free exchange',
            'listing_type' => Listing::TYPE_EXCHANGE,
            'price' => null,
        ]);

        $response = $this->actingAs($this->verifiedUser())
            ->get(route('marketplace.index', ['sort' => 'price_asc']))
            ->assertOk()
            ->assertSee('Lower priced item')
            ->assertSee('Higher priced item')
            ->assertDontSee('Price-free exchange');

        $this->assertLessThan(
            strpos($response->getContent(), 'Higher priced item'),
            strpos($response->getContent(), 'Lower priced item'),
        );
    }

    public function test_inactive_listing_detail_is_not_available(): void
    {
        $listing = $this->listing($this->verifiedUser(), ['is_active' => false]);

        $this->actingAs($this->verifiedUser())
            ->get(route('marketplace.show', $listing))
            ->assertNotFound();
    }

    public function test_verified_user_can_create_sale_listing_with_server_assigned_owner(): void
    {
        $owner = $this->verifiedUser();
        $category = Category::factory()->create();
        $anotherUser = $this->verifiedUser();

        $this->actingAs($owner)
            ->post(route('marketplace.store'), $this->listingData($category, [
                'user_id' => $anotherUser->id,
                'status' => Listing::STATUS_SOLD,
                'is_active' => false,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('listings', [
            'title' => 'New campus listing',
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'status' => Listing::STATUS_AVAILABLE,
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('listings', [
            'title' => 'New campus listing',
            'user_id' => $anotherUser->id,
        ]);
    }

    public function test_exchange_listing_does_not_require_or_store_a_price(): void
    {
        $owner = $this->verifiedUser();
        $category = Category::factory()->create();

        $this->actingAs($owner)
            ->post(route('marketplace.store'), $this->listingData($category, [
                'listing_type' => Listing::TYPE_EXCHANGE,
                'price' => '',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('listings', [
            'user_id' => $owner->id,
            'listing_type' => Listing::TYPE_EXCHANGE,
            'price' => null,
        ]);
    }

    public function test_creation_rejects_missing_or_non_positive_sale_price(): void
    {
        $owner = $this->verifiedUser();
        $category = Category::factory()->create();

        foreach (['', '0', '-4'] as $price) {
            $this->actingAs($owner)
                ->post(route('marketplace.store'), $this->listingData($category, ['price' => $price]))
                ->assertSessionHasErrors('price');
        }

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_creation_rejects_nonexistent_or_inactive_category(): void
    {
        $owner = $this->verifiedUser();

        $this->actingAs($owner)
            ->post(route('marketplace.store'), $this->listingData(null, ['category_id' => 99999]))
            ->assertSessionHasErrors('category_id');

        $inactiveCategory = Category::factory()->create(['is_active' => false]);
        $this->actingAs($owner)
            ->post(route('marketplace.store'), $this->listingData($inactiveCategory))
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_creation_rejects_zero_or_negative_daily_stock(): void
    {
        $owner = $this->verifiedUser();
        $category = Category::factory()->create();

        foreach (['0', '-2'] as $stock) {
            $this->actingAs($owner)
                ->post(route('marketplace.store'), $this->listingData($category, ['stock' => $stock]))
                ->assertSessionHasErrors('stock');
        }
    }

    public function test_creation_stores_uploaded_images_and_listing_image_records(): void
    {
        $owner = $this->verifiedUser();
        $category = Category::factory()->create();

        $this->actingAs($owner)
            ->post(route('marketplace.store'), $this->listingData($category, [
                'images' => [
                    $this->fakePng('front.png'),
                    $this->fakePng('side.png'),
                ],
            ]))
            ->assertRedirect();

        $listing = Listing::query()->firstOrFail();
        $this->assertCount(2, $listing->images);

        foreach ($listing->images as $image) {
            Storage::disk('public')->assertExists($image->path);
            $this->assertStringStartsWith('listings/'.$owner->id.'/', $image->path);
        }
    }

    public function test_creation_rejects_unsupported_image_types_and_more_than_five_images(): void
    {
        $owner = $this->verifiedUser();
        $category = Category::factory()->create();

        $this->actingAs($owner)
            ->post(route('marketplace.store'), $this->listingData($category, [
                'images' => [UploadedFile::fake()->createWithContent('payload.txt', 'plain text')],
            ]))
            ->assertSessionHasErrors('images.0');

        $this->actingAs($owner)
            ->post(route('marketplace.store'), $this->listingData($category, [
                'images' => array_map(
                    fn (int $number) => $this->fakePng("image-{$number}.png"),
                    range(1, 6),
                ),
            ]))
            ->assertSessionHasErrors('images');

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_creation_rejects_images_larger_than_five_megabytes(): void
    {
        $owner = $this->verifiedUser();
        $category = Category::factory()->create();
        $contents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l2cAAAAASUVORK5CYII=',
            true,
        );
        $oversizedImage = UploadedFile::fake()->createWithContent(
            'large.png',
            $contents.str_repeat("\0", 5 * 1024 * 1024 + 1),
        );

        $this->actingAs($owner)
            ->post(route('marketplace.store'), $this->listingData($category, [
                'images' => [$oversizedImage],
            ]))
            ->assertSessionHasErrors('images.0');

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_owner_can_edit_listing_and_add_or_remove_images(): void
    {
        $owner = $this->verifiedUser();
        $listing = $this->listing($owner);
        $oldImage = $listing->images()->create(['path' => 'listings/'.$owner->id.'/old.jpg']);
        Storage::disk('public')->put($oldImage->path, 'old');
        $category = Category::factory()->create();

        $this->actingAs($owner)
            ->put(route('marketplace.update', $listing), $this->listingData($category, [
                'title' => 'Updated campus listing',
                'delete_images' => [$oldImage->id],
                'images' => [$this->fakePng('new.png')],
            ]))
            ->assertRedirect(route('marketplace.show', $listing));

        $this->assertSame('Updated campus listing', $listing->fresh()->title);
        $this->assertDatabaseMissing('listing_images', ['id' => $oldImage->id]);
        Storage::disk('public')->assertMissing($oldImage->path);
        $this->assertCount(1, $listing->fresh()->images);
        Storage::disk('public')->assertExists($listing->fresh()->images->first()->path);
    }

    public function test_another_user_cannot_edit_or_delete_listing(): void
    {
        $listing = $this->listing($this->verifiedUser());
        $otherUser = $this->verifiedUser();

        $this->actingAs($otherUser)
            ->get(route('marketplace.edit', $listing))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->put(route('marketplace.update', $listing), $this->listingData($listing->category))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->delete(route('marketplace.destroy', $listing))
            ->assertForbidden();

        $this->assertDatabaseHas('listings', ['id' => $listing->id]);
    }

    public function test_owner_can_delete_listing_and_its_image_files(): void
    {
        $owner = $this->verifiedUser();
        $listing = $this->listing($owner);
        $image = $listing->images()->create(['path' => 'listings/'.$owner->id.'/delete.jpg']);
        Storage::disk('public')->put($image->path, 'image');

        $this->actingAs($owner)
            ->delete(route('marketplace.destroy', $listing))
            ->assertRedirect(route('my-listings'));

        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_owner_can_delete_only_an_image_belonging_to_their_listing(): void
    {
        $owner = $this->verifiedUser();
        $listing = $this->listing($owner);
        $image = $listing->images()->create(['path' => 'listings/'.$owner->id.'/remove.jpg']);
        Storage::disk('public')->put($image->path, 'image');

        $this->actingAs($owner)
            ->delete(route('marketplace.images.destroy', [$listing, $image->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_user_cannot_delete_an_image_from_another_users_listing(): void
    {
        $owner = $this->verifiedUser();
        $listing = $this->listing($owner);
        $image = $listing->images()->create(['path' => 'listings/'.$owner->id.'/private.jpg']);
        Storage::disk('public')->put($image->path, 'image');

        $this->actingAs($this->verifiedUser())
            ->delete(route('marketplace.images.destroy', [$listing, $image->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('listing_images', ['id' => $image->id]);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_daily_listing_accepts_only_daily_statuses_and_valid_stock(): void
    {
        $owner = $this->verifiedUser();
        $listing = $this->listing($owner, [
            'availability_type' => Listing::AVAILABILITY_DAILY,
            'stock' => 3,
        ]);

        $this->actingAs($owner)
            ->patch(route('marketplace.availability', $listing), [
                'status' => Listing::STATUS_SOLD_OUT_TODAY,
                'stock' => 0,
            ])
            ->assertRedirect();
        $this->assertSame(Listing::STATUS_SOLD_OUT_TODAY, $listing->fresh()->status);
        $this->assertSame(0, $listing->fresh()->stock);

        $this->actingAs($owner)
            ->patch(route('marketplace.availability', $listing), [
                'status' => Listing::STATUS_AVAILABLE,
                'stock' => 0,
            ])
            ->assertSessionHasErrors('stock');

        $this->actingAs($owner)
            ->patch(route('marketplace.availability', $listing), [
                'status' => Listing::STATUS_RESERVED,
                'stock' => 2,
            ])
            ->assertSessionHasErrors('status');
    }

    public function test_unique_listing_accepts_only_unique_statuses_and_does_not_change_stock(): void
    {
        $owner = $this->verifiedUser();
        $listing = $this->listing($owner, [
            'availability_type' => Listing::AVAILABILITY_UNIQUE,
            'stock' => null,
        ]);

        foreach ([
            Listing::STATUS_RESERVED,
            Listing::STATUS_SOLD,
            Listing::STATUS_EXCHANGED,
            Listing::STATUS_AVAILABLE,
        ] as $status) {
            $this->actingAs($owner)
                ->patch(route('marketplace.availability', $listing), ['status' => $status])
                ->assertRedirect();
            $this->assertSame($status, $listing->fresh()->status);
            $this->assertNull($listing->fresh()->stock);
        }

        $this->actingAs($owner)
            ->patch(route('marketplace.availability', $listing), ['status' => Listing::STATUS_SOLD_OUT_TODAY])
            ->assertSessionHasErrors('status');

        $this->actingAs($owner)
            ->patch(route('marketplace.availability', $listing), [
                'status' => Listing::STATUS_SOLD,
                'stock' => 0,
            ])
            ->assertSessionHasErrors('stock');
    }

    public function test_inventory_update_rejects_negative_stock(): void
    {
        $owner = $this->verifiedUser();
        $listing = $this->listing($owner, [
            'availability_type' => Listing::AVAILABILITY_DAILY,
            'stock' => 2,
        ]);

        $this->actingAs($owner)
            ->patch(route('marketplace.availability', $listing), [
                'status' => Listing::STATUS_AVAILABLE,
                'stock' => -1,
            ])
            ->assertSessionHasErrors('stock');

        $this->assertSame(2, $listing->fresh()->stock);
    }

    public function test_availability_changes_are_restricted_to_the_listing_owner(): void
    {
        $listing = $this->listing($this->verifiedUser());

        $this->actingAs($this->verifiedUser())
            ->patch(route('marketplace.availability', $listing), ['status' => Listing::STATUS_SOLD_OUT_TODAY])
            ->assertForbidden();
    }

    public function test_my_listings_shows_only_authenticated_users_publications(): void
    {
        $owner = $this->verifiedUser();
        $mine = $this->listing($owner, ['title' => 'My private listing']);
        $this->listing($this->verifiedUser(), ['title' => 'Someone elses listing']);

        $this->actingAs($owner)
            ->get(route('my-listings'))
            ->assertOk()
            ->assertSee($mine->title)
            ->assertDontSee('Someone elses listing');
    }

    public function test_my_listings_has_an_empty_state(): void
    {
        $this->actingAs($this->verifiedUser())
            ->get(route('my-listings'))
            ->assertOk()
            ->assertSee('Aún no tienes publicaciones');
    }

    public function test_catalog_pagination_preserves_active_filters(): void
    {
        $owner = $this->verifiedUser();
        $category = Category::factory()->create();
        Listing::factory()->count(13)->for($owner)->for($category)->create([
            'title' => 'Campus item',
            'listing_type' => Listing::TYPE_SALE,
            'availability_type' => Listing::AVAILABILITY_DAILY,
        ]);

        $response = $this->actingAs($this->verifiedUser())
            ->get(route('marketplace.index', [
                'search' => 'Campus',
                'category' => $category->id,
                'type' => Listing::TYPE_SALE,
            ]))
            ->assertOk()
            ->assertSee('page=2');

        $this->assertStringContainsString('search=Campus', $response->getContent());
        $this->assertStringContainsString('category='.$category->id, $response->getContent());
        $this->assertStringContainsString('type=sale', $response->getContent());
    }

    private function verifiedUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->markEmailAsVerified();

        return $user;
    }

    private function listing(User $owner, array $attributes = []): Listing
    {
        return Listing::factory()->for($owner)->create($attributes);
    }

    private function listingData(?Category $category, array $overrides = []): array
    {
        return array_merge([
            'title' => 'New campus listing',
            'description' => 'A useful item for students.',
            'category_id' => $category?->id,
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
