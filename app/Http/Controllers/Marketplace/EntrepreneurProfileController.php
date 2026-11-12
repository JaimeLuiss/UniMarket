<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\EntrepreneurProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class EntrepreneurProfileController extends Controller
{
    public function showOwn(Request $request): View|RedirectResponse
    {
        $profile = $request->user()->entrepreneurProfile;

        if (! $profile) {
            return redirect()->route('my-entrepreneur.create')
                ->with('status', 'Aún no tienes un emprendimiento. Crea tu perfil para comenzar.');
        }

        $listings = $profile->listings()
            ->with(['category', 'images'])
            ->withCount('favoritedBy')
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('entrepreneurs.mine', compact('profile', 'listings'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->entrepreneurProfile()->exists()) {
            return redirect()->route('my-entrepreneur.edit')
                ->with('status', 'Ya tienes un perfil de emprendimiento.');
        }

        return view('entrepreneurs.form', [
            'profile' => null,
            'entrepreneurProfiles' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->entrepreneurProfile()->exists()) {
            return redirect()->route('my-entrepreneur.edit')
                ->with('status', 'Ya tienes un perfil de emprendimiento.');
        }

        $validated = $request->validate($this->profileRules());
        $logoPath = ($validated['logo'] ?? null)?->store('entrepreneurs/'.$request->user()->id, 'public');

        try {
            $profile = DB::transaction(fn () => $request->user()->entrepreneurProfile()->create([
                'business_name' => $validated['business_name'],
                'slug' => $this->uniqueSlug($validated['business_name']),
                'description' => $validated['description'],
                'logo_path' => $logoPath,
                'campus_location' => $validated['campus_location'] ?? null,
                'is_active' => true,
            ]));
        } catch (Throwable $exception) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            throw $exception;
        }

        return redirect()->route('my-entrepreneur')->with('status', 'Tu perfil de emprendimiento fue creado.');
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $profile = $request->user()->entrepreneurProfile;

        if (! $profile) {
            return redirect()->route('my-entrepreneur.create')
                ->with('status', 'Crea primero tu perfil de emprendimiento.');
        }

        return view('entrepreneurs.form', compact('profile'));
    }

    public function update(Request $request): RedirectResponse
    {
        $profile = $request->user()->entrepreneurProfile;

        if (! $profile) {
            return redirect()->route('my-entrepreneur.create')
                ->with('status', 'Crea primero tu perfil de emprendimiento.');
        }

        $validated = $request->validate($this->profileRules());
        $newLogoPath = ($validated['logo'] ?? null)?->store('entrepreneurs/'.$request->user()->id, 'public');
        $oldLogoPath = $profile->logo_path;

        try {
            DB::transaction(function () use ($validated, $profile, $newLogoPath): void {
                $profile->update([
                    'business_name' => $validated['business_name'],
                    'slug' => $this->uniqueSlug($validated['business_name'], $profile->id),
                    'description' => $validated['description'],
                    'logo_path' => $newLogoPath ?? $profile->logo_path,
                    'campus_location' => $validated['campus_location'] ?? null,
                    'is_active' => (bool) $validated['is_active'],
                ]);
            });
        } catch (Throwable $exception) {
            if ($newLogoPath) {
                Storage::disk('public')->delete($newLogoPath);
            }

            throw $exception;
        }

        if ($newLogoPath && $oldLogoPath) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        return redirect()->route('my-entrepreneur')->with('status', 'Tu perfil de emprendimiento fue actualizado.');
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate(['is_active' => ['required', 'boolean']]);
        $profile = $request->user()->entrepreneurProfile;

        if (! $profile) {
            return redirect()->route('my-entrepreneur.create')
                ->with('status', 'Crea primero tu perfil de emprendimiento.');
        }

        $profile->update(['is_active' => (bool) $validated['is_active']]);

        return back()->with(
            'status',
            $profile->is_active ? 'Tu emprendimiento ahora está activo.' : 'Tu emprendimiento fue desactivado.'
        );
    }

    public function publicShow(string $slug): View
    {
        $profile = EntrepreneurProfile::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->withCount('followers')
            ->firstOrFail();

        $listings = $profile->listings()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->whereHas('user', fn (Builder $query) => $query->whereNotNull('email_verified_at'))
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
            ->with(['category', 'images', 'entrepreneurProfile'])
            ->withCount('favoritedBy')
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $isFollowing = auth()->user()->favoriteEntrepreneurs()->whereKey($profile->id)->exists();

        return view('entrepreneurs.show', compact('profile', 'listings', 'isFollowing'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function profileRules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
            'campus_location' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    private function uniqueSlug(string $businessName, ?int $ignoreProfileId = null): string
    {
        $baseSlug = Str::slug($businessName) ?: 'emprendimiento';
        $slug = $baseSlug;
        $suffix = 2;

        while (EntrepreneurProfile::query()
            ->where('slug', $slug)
            ->when($ignoreProfileId, fn (Builder $query) => $query->whereKeyNot($ignoreProfileId))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
