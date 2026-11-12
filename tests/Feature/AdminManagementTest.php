<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\EntrepreneurProfile;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_counts_from_the_database(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $reporter = User::factory()->create();
        $category = Category::factory()->create();
        $profile = EntrepreneurProfile::factory()->for($owner)->create();
        $listing = Listing::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'entrepreneur_profile_id' => $profile->id,
        ]);

        Report::query()->create([
            'reporter_id' => $reporter->id,
            'listing_id' => $listing->id,
            'reported_user_id' => $owner->id,
            'reason' => Report::REASON_SPAM,
            'description' => 'Este reporte está pendiente de revisión.',
            'status' => Report::STATUS_PENDING,
        ]);
        Report::query()->create([
            'reporter_id' => $reporter->id,
            'listing_id' => $listing->id,
            'reported_user_id' => $owner->id,
            'reason' => Report::REASON_OTHER,
            'description' => 'Este reporte ya fue resuelto.',
            'status' => Report::STATUS_RESOLVED,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics === [
                'users' => 3,
                'listings' => 1,
                'availableListings' => 1,
                'activeEntrepreneurs' => 1,
                'pendingReports' => 1,
                'resolvedReports' => 1,
            ]);
    }

    public function test_student_cannot_access_admin_pages_and_guest_is_redirected(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));

        $student = User::factory()->create();

        $this->actingAs($student)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.listings.index'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_admin_can_filter_users_without_exposing_credentials(): void
    {
        $admin = $this->admin();
        $matchingUser = User::factory()->create([
            'name' => 'María Campus',
            'email' => 'maria@campus.example',
        ]);
        Listing::factory()->create(['user_id' => $matchingUser->id]);
        User::factory()->create(['name' => 'Other Student', 'email' => 'other@campus.example']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'maria', 'role' => User::ROLE_STUDENT]))
            ->assertOk()
            ->assertSee($matchingUser->name)
            ->assertSee($matchingUser->email)
            ->assertDontSee('other@campus.example')
            ->assertDontSee('remember_token')
            ->assertDontSee('password');
    }

    public function test_admin_can_browse_and_filter_all_listings(): void
    {
        $admin = $this->admin();
        $active = Listing::factory()->create(['title' => 'Admin-visible listing']);
        Listing::factory()->create(['title' => 'Hidden listing', 'is_active' => false]);

        $this->actingAs($admin)
            ->get(route('admin.listings.index', ['search' => 'Admin-visible', 'active' => 'active']))
            ->assertOk()
            ->assertSee($active->title)
            ->assertDontSee('Hidden listing');
    }

    public function test_admin_can_list_categories_and_duplicate_names_are_rejected(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create(['name' => 'Campus Supplies']);

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee($category->name);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'campus supplies',
                'description' => 'Otra descripción.',
                'sort_order' => 4,
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_admin_can_create_category_with_automatically_unique_slug(): void
    {
        $admin = $this->admin();
        Category::factory()->create(['name' => 'Eco market', 'slug' => 'eco-market']);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Eco-market',
                'description' => 'Artículos sostenibles para estudiantes.',
                'sort_order' => 8,
                'slug' => 'client-controlled-slug',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Eco-market',
            'slug' => 'eco-market-2',
            'sort_order' => 8,
        ]);
        $this->assertDatabaseMissing('categories', ['slug' => 'client-controlled-slug']);
    }

    public function test_admin_can_edit_category_and_regenerate_its_slug(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create(['name' => 'Old supplies', 'slug' => 'old-supplies']);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), [
                'name' => 'New supplies',
                'description' => 'Updated category.',
                'sort_order' => 12,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New supplies',
            'slug' => 'new-supplies',
            'sort_order' => 12,
        ]);
    }

    public function test_category_can_be_deactivated_and_is_hidden_from_new_listing_forms(): void
    {
        $admin = $this->admin();
        $student = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Inactive supplies']);

        $this->actingAs($admin)
            ->patch(route('admin.categories.status', $category), ['is_active' => false])
            ->assertRedirect();
        $this->assertFalse($category->fresh()->is_active);

        $this->actingAs($student)
            ->get(route('marketplace.create'))
            ->assertOk()
            ->assertDontSee($category->name);

        $this->actingAs($admin)
            ->patch(route('admin.categories.status', $category), ['is_active' => true])
            ->assertRedirect();
        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_category_with_publications_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();
        Listing::factory()->create(['category_id' => $category->id]);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_unused_category_can_be_deleted(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        return $admin;
    }
}
