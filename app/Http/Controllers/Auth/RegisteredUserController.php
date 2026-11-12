<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedDomain = trim((string) config('unimarket.allowed_email_domain'));

        if ($allowedDomain === '' && app()->environment('production')) {
            abort(503, 'El dominio institucional permitido no está configurado.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
                function (string $attribute, mixed $value, \Closure $fail) use ($allowedDomain): void {
                    if ($allowedDomain === '') {
                        return;
                    }

                    $emailDomain = Str::lower(Str::afterLast((string) $value, '@'));
                    $expectedDomain = Str::lower(ltrim($allowedDomain, '@'));

                    if ($emailDomain !== $expectedDomain) {
                        $fail('Debes utilizar tu correo institucional.');
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s().-]{7,30}$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
        ]);
        $user->role = User::ROLE_STUDENT;
        $user->save();

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }
}
