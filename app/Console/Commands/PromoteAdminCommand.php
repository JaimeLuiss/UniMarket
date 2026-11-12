<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteAdminCommand extends Command
{
    protected $signature = 'unimarket {email : Correo de una cuenta existente y verificada}';

    protected $description = 'Promueve una cuenta verificada existente al rol administrador';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $user) {
            $this->error('No existe una cuenta con ese correo.');

            return self::FAILURE;
        }

        if (! $user->hasVerifiedEmail()) {
            $this->error('La cuenta debe verificar su correo antes de convertirse en administradora.');

            return self::FAILURE;
        }

        if ($user->role === User::ROLE_ADMIN) {
            $this->info('La cuenta ya tiene el rol administrador.');

            return self::SUCCESS;
        }

        if (! $this->confirm("¿Confirmas promover {$user->email} al rol administrador?")) {
            $this->warn('No se realizaron cambios.');

            return self::SUCCESS;
        }

        $user->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->info("La cuenta {$user->email} ahora es administradora.");

        return self::SUCCESS;
    }
}
