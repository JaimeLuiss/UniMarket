<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_student_can_register_and_receives_email_verification_notification(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Jamie Student',
            'email' => 'jamie@example.test',
            'phone' => '+1 555 123 4567',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
            'terms' => '1',
            'role' => User::ROLE_ADMIN,
        ]);

        $user = User::query()->where('email', 'jamie@example.test')->firstOrFail();

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame(User::ROLE_STUDENT, $user->role);
        $this->assertSame('+1 555 123 4567', $user->phone);
        $this->assertTrue(Hash::check('secure-pass-123', $user->password));
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);

        $this->post(route('register.store'), $this->registrationData([
            'email' => 'taken@example.test',
        ]))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_rejects_password_mismatch_and_unaccepted_terms(): void
    {
        $this->post(route('register.store'), $this->registrationData([
            'password_confirmation' => 'different-password',
            'terms' => '0',
        ]))
            ->assertSessionHasErrors(['password', 'terms']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_passwords_shorter_than_eight_characters(): void
    {
        $this->post(route('register.store'), $this->registrationData([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_invalid_phone_number(): void
    {
        $this->post(route('register.store'), $this->registrationData([
            'phone' => 'call-me',
        ]))
            ->assertSessionHasErrors('phone');
    }

    public function test_registration_enforces_configured_domain_without_case_sensitivity(): void
    {
        config(['unimarket.allowed_email_domain' => 'campus.example']);

        $this->post(route('register.store'), $this->registrationData([
            'email' => 'student@CAMPUS.EXAMPLE',
        ]))
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('users', ['email' => 'student@CAMPUS.EXAMPLE']);
    }

    public function test_registration_rejects_an_email_outside_configured_domain(): void
    {
        config(['unimarket.allowed_email_domain' => 'campus.example']);

        $this->post(route('register.store'), $this->registrationData([
            'email' => 'student@other.example',
        ]))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_production_registration_is_blocked_when_domain_is_not_configured(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['unimarket.allowed_email_domain' => null]);

        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->post(route('register.store'), $this->registrationData())
            ->assertServiceUnavailable();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_user_can_log_in_and_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.test',
            'password' => 'correct-password',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_repeated_login_failures_are_rate_limited(): void
    {
        $user = User::factory()->create(['email' => 'limited@example.test']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertTooManyRequests();
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_email_verification_uses_signed_link_and_marks_user_verified(): void
    {
        $user = User::factory()->unverified()->create();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())],
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_email_verification_link_rejects_invalid_signature(): void
    {
        $user = User::factory()->unverified()->create();
        $url = route('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->get($url)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verification_notification_can_be_resent(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_private_routes_require_authentication_and_verified_email(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $unverifiedUser = User::factory()->unverified()->create();

        $this->actingAs($unverifiedUser)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_student_is_forbidden_from_admin_route_and_admin_is_allowed(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('admin.dashboard'))->assertForbidden();

        $admin = User::factory()->create();
        $admin->role = User::ROLE_ADMIN;
        $admin->save();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Panel administrativo');
    }

    public function test_user_can_request_a_password_reset_and_change_password_with_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.test',
            'password' => 'old-password-123',
        ]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });
        $this->assertNotNull($token);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_password_reset_rejects_invalid_token_and_mismatched_confirmation(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.test']);

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_password_reset_request_response_does_not_disclose_unknown_accounts(): void
    {
        $this->post(route('password.email'), ['email' => 'unknown@example.test'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Taylor Student',
            'email' => 'taylor@example.test',
            'phone' => '',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
            'terms' => '1',
        ], $overrides);
    }
}
