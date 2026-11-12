<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_report_another_users_listing_with_server_assigned_fields(): void
    {
        $reporter = User::factory()->create();
        $owner = User::factory()->create();
        $listing = $this->listing($owner);

        $this->actingAs($reporter)
            ->post(route('marketplace.reports.store', $listing), [
                'reason' => Report::REASON_POSSIBLE_FRAUD,
                'description' => 'La publicación incluye datos que parecen fraudulentos.',
                'reporter_id' => $owner->id,
                'reported_user_id' => $reporter->id,
                'listing_id' => 99999,
                'status' => Report::STATUS_RESOLVED,
                'reviewed_by' => $reporter->id,
            ])
            ->assertRedirect(route('marketplace.show', $listing))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reported_user_id' => $owner->id,
            'listing_id' => $listing->id,
            'reason' => Report::REASON_POSSIBLE_FRAUD,
            'status' => Report::STATUS_PENDING,
            'reviewed_by' => null,
        ]);
    }

    public function test_report_form_is_available_to_non_owners_only(): void
    {
        $owner = User::factory()->create();
        $listing = $this->listing($owner);
        $reporter = User::factory()->create();

        $this->actingAs($reporter)
            ->get(route('marketplace.show', $listing))
            ->assertOk()
            ->assertSee('Reportar esta publicación');

        $this->actingAs($owner)
            ->get(route('marketplace.show', $listing))
            ->assertOk()
            ->assertDontSee('Reportar esta publicación');
    }

    public function test_student_cannot_report_own_listing(): void
    {
        $owner = User::factory()->create();
        $listing = $this->listing($owner);

        $this->actingAs($owner)
            ->post(route('marketplace.reports.store', $listing), [
                'reason' => Report::REASON_SPAM,
                'description' => 'Descripción suficiente para probar el rechazo.',
            ])
            ->assertSessionHasErrors('report');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_report_route_rejects_nonexistent_listing(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/marketplace/999999/reports', [
                'reason' => Report::REASON_SPAM,
                'description' => 'Descripción suficiente para probar el reporte.',
            ])
            ->assertNotFound();
    }

    public function test_report_route_requires_authentication_and_verified_email(): void
    {
        $listing = $this->listing();

        $this->post(route('marketplace.reports.store', $listing), [
            'reason' => Report::REASON_SPAM,
            'description' => 'Descripción suficiente para probar el reporte.',
        ])->assertRedirect(route('login'));

        $unverified = User::factory()->unverified()->create();

        $this->actingAs($unverified)
            ->post(route('marketplace.reports.store', $listing), [
                'reason' => Report::REASON_SPAM,
                'description' => 'Descripción suficiente para probar el reporte.',
            ])
            ->assertRedirect(route('verification.notice'));
    }

    public function test_report_reason_and_description_are_validated(): void
    {
        $reporter = User::factory()->create();
        $listing = $this->listing();

        $this->actingAs($reporter)
            ->post(route('marketplace.reports.store', $listing), [
                'reason' => 'made-up-reason',
                'description' => 'short',
            ])
            ->assertSessionHasErrors(['reason', 'description']);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_duplicate_report_for_same_listing_is_blocked(): void
    {
        $reporter = User::factory()->create();
        $listing = $this->listing();
        $payload = [
            'reason' => Report::REASON_INCORRECT_INFORMATION,
            'description' => 'La información de disponibilidad no es correcta.',
        ];

        $this->actingAs($reporter)
            ->post(route('marketplace.reports.store', $listing), $payload)
            ->assertRedirect();
        $this->actingAs($reporter)
            ->post(route('marketplace.reports.store', $listing), $payload)
            ->assertSessionHasErrors('report');

        $this->assertDatabaseCount('reports', 1);
    }

    public function test_report_submission_is_rate_limited_per_authenticated_user(): void
    {
        $reporter = User::factory()->create();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $listing = $this->listing();

            $this->actingAs($reporter)
                ->post(route('marketplace.reports.store', $listing), [
                    'reason' => Report::REASON_SPAM,
                    'description' => 'Reporte de demostración número '.$attempt.'.',
                ])
                ->assertRedirect();
        }

        $lastListing = $this->listing();
        $this->actingAs($reporter)
            ->post(route('marketplace.reports.store', $lastListing), [
                'reason' => Report::REASON_SPAM,
                'description' => 'Este reporte excede el límite horario.',
            ])
            ->assertTooManyRequests();

        $this->assertDatabaseCount('reports', 10);
    }

    public function test_reporter_cannot_access_administrative_report_pages(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_admin_can_filter_reports_and_view_report_details(): void
    {
        $admin = $this->admin();
        $listing = $this->listing();
        $report = $this->report($listing, ['reason' => Report::REASON_SPAM]);
        $otherListing = $this->listing();
        $otherReport = $this->report($otherListing, ['reason' => Report::REASON_OTHER]);

        $this->actingAs($admin)
            ->get(route('admin.reports.index', ['status' => Report::STATUS_PENDING, 'reason' => Report::REASON_SPAM]))
            ->assertOk()
            ->assertSee($report->listing->title)
            ->assertDontSee($otherReport->listing->title);

        $this->actingAs($admin)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee($report->description)
            ->assertSee($report->reporter->email)
            ->assertSee($report->reportedUser->email)
            ->assertSee($listing->title);
    }

    public function test_admin_can_mark_pending_report_as_reviewed(): void
    {
        $admin = $this->admin();
        $report = $this->report($this->listing());

        $this->actingAs($admin)
            ->patch(route('admin.reports.review', $report), ['resolution_notes' => 'Se inició la revisión.'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => Report::STATUS_REVIEWED,
            'reviewed_by' => $admin->id,
        ]);
        $this->assertNotNull($report->fresh()->reviewed_at);
        $this->assertStringContainsString('Se inició la revisión.', $report->fresh()->resolution_notes);
    }

    public function test_admin_can_resolve_or_reject_pending_reports(): void
    {
        $admin = $this->admin();
        $resolved = $this->report($this->listing());
        $rejected = $this->report($this->listing());

        $this->actingAs($admin)
            ->patch(route('admin.reports.resolve', $resolved), ['resolution_notes' => 'La publicación incumple las reglas.'])
            ->assertRedirect();
        $this->actingAs($admin)
            ->patch(route('admin.reports.reject', $rejected), ['resolution_notes' => 'No se encontró una infracción.'])
            ->assertRedirect();

        $this->assertSame(Report::STATUS_RESOLVED, $resolved->fresh()->status);
        $this->assertSame(Report::STATUS_REJECTED, $rejected->fresh()->status);
        $this->assertSame($admin->id, $resolved->fresh()->reviewed_by);
        $this->assertNotNull($resolved->fresh()->reviewed_at);
    }

    public function test_report_review_history_is_appended_instead_of_overwritten(): void
    {
        $admin = $this->admin();
        $report = $this->report($this->listing());

        $this->actingAs($admin)
            ->patch(route('admin.reports.review', $report), ['resolution_notes' => 'Primera observación.'])
            ->assertRedirect();
        $this->actingAs($admin)
            ->patch(route('admin.reports.resolve', $report), ['resolution_notes' => 'Observación final.'])
            ->assertRedirect();

        $fresh = $report->fresh();
        $this->assertSame(Report::STATUS_RESOLVED, $fresh->status);
        $this->assertStringContainsString('Primera observación.', $fresh->resolution_notes);
        $this->assertStringContainsString('Observación final.', $fresh->resolution_notes);
        $this->assertSame($admin->id, $fresh->reviewed_by);
    }

    public function test_closed_report_cannot_be_changed_to_an_arbitrary_or_new_status(): void
    {
        $admin = $this->admin();
        $report = $this->report($this->listing(), ['status' => Report::STATUS_RESOLVED]);

        $this->actingAs($admin)
            ->patch(route('admin.reports.reject', $report), ['status' => 'anything'])
            ->assertSessionHasErrors('status');

        $this->assertSame(Report::STATUS_RESOLVED, $report->fresh()->status);
    }

    public function test_admin_can_deactivate_and_reactivate_listing_without_changing_commercial_status(): void
    {
        $admin = $this->admin();
        $listing = $this->listing(null, ['status' => Listing::STATUS_RESERVED]);
        $report = $this->report($listing);

        $this->actingAs($admin)
            ->patch(route('admin.reports.listing-moderation', $report), ['is_active' => false])
            ->assertRedirect();
        $this->assertFalse($listing->fresh()->is_active);
        $this->assertSame(Listing::STATUS_RESERVED, $listing->fresh()->status);

        $this->actingAs($admin)
            ->patch(route('admin.reports.listing-moderation', $report), ['is_active' => true])
            ->assertRedirect();
        $this->assertTrue($listing->fresh()->is_active);
        $this->assertSame(Listing::STATUS_RESERVED, $listing->fresh()->status);
    }

    public function test_owner_cannot_reactivate_an_administratively_deactivated_listing(): void
    {
        $owner = User::factory()->create();
        $listing = $this->listing($owner, ['is_active' => false]);
        $report = $this->report($listing);
        $category = Category::query()->findOrFail($listing->category_id);

        $this->actingAs($owner)
            ->put(route('marketplace.update', $listing), [
                'title' => $listing->title,
                'description' => $listing->description,
                'category_id' => $category->id,
                'listing_type' => Listing::TYPE_SALE,
                'price' => 20,
                'availability_type' => Listing::AVAILABILITY_DAILY,
                'stock' => 3,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->patch(route('marketplace.availability', $listing), [
                'status' => Listing::STATUS_AVAILABLE,
                'stock' => 3,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertFalse($listing->fresh()->is_active);

        $this->actingAs($owner)
            ->patch(route('admin.reports.listing-moderation', $report), ['is_active' => true])
            ->assertForbidden();
        $this->assertFalse($listing->fresh()->is_active);
    }

    private function listing(?User $owner = null, array $attributes = []): Listing
    {
        $attributes['user_id'] ??= ($owner ?? User::factory()->create())->id;
        $attributes['category_id'] ??= Category::factory()->create()->id;

        return Listing::factory()->create($attributes);
    }

    private function report(Listing $listing, array $attributes = []): Report
    {
        return Report::query()->create(array_merge([
            'reporter_id' => User::factory()->create()->id,
            'listing_id' => $listing->id,
            'reported_user_id' => $listing->user_id,
            'reason' => Report::REASON_OTHER,
            'description' => 'Descripción de prueba del reporte.',
            'status' => Report::STATUS_PENDING,
        ], $attributes));
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        return $admin;
    }
}
