<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoteAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_promotes_an_existing_verified_user_after_confirmation_without_changing_password(): void
    {
        $user = User::factory()->create(['email' => 'Student@campus.example']);
        $passwordHash = $user->password;

        $this->artisan('unimarket', ['email' => 'student@campus.example'])
            ->expectsConfirmation('¿Confirmas promover Student@campus.example al rol administrador?', 'yes')
            ->expectsOutput('La cuenta Student@campus.example ahora es administradora.')
            ->assertExitCode(0);

        $this->assertSame(User::ROLE_ADMIN, $user->fresh()->role);
        $this->assertSame($passwordHash, $user->fresh()->password);
    }

    public function test_command_fails_for_an_unknown_email(): void
    {
        $this->artisan('unimarket', ['email' => 'missing@campus.example'])
            ->expectsOutput('No existe una cuenta con ese correo.')
            ->assertExitCode(1);
    }

    public function test_command_cannot_promote_an_unverified_account(): void
    {
        $user = User::factory()->unverified()->create();

        $this->artisan('unimarket', ['email' => $user->email])
            ->expectsOutput('La cuenta debe verificar su correo antes de convertirse en administradora.')
            ->assertExitCode(1);

        $this->assertSame(User::ROLE_STUDENT, $user->fresh()->role);
    }

    public function test_cancelled_confirmation_does_not_change_the_role(): void
    {
        $user = User::factory()->create();

        $this->artisan('unimarket', ['email' => $user->email])
            ->expectsConfirmation("¿Confirmas promover {$user->email} al rol administrador?", 'no')
            ->expectsOutput('No se realizaron cambios.')
            ->assertExitCode(0);

        $this->assertSame(User::ROLE_STUDENT, $user->fresh()->role);
    }
}
