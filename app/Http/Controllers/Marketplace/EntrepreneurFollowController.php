<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\EntrepreneurProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EntrepreneurFollowController extends Controller
{
    public function index(Request $request): View
    {
        $profiles = $request->user()->favoriteEntrepreneurs()
            ->withCount('followers')
            ->withCount([
                'listings' => fn (Builder $query) => $query
                    ->where('is_active', true)
                    ->whereNotNull('published_at'),
            ])
            ->orderBy('business_name')
            ->paginate(12)
            ->withQueryString();

        return view('entrepreneurs.following', compact('profiles'));
    }

    public function store(Request $request, EntrepreneurProfile $entrepreneurProfile): RedirectResponse
    {
        if ($entrepreneurProfile->user_id === $request->user()->id) {
            throw ValidationException::withMessages([
                'follow' => 'No puedes seguir tu propio emprendimiento.',
            ]);
        }

        if (! $entrepreneurProfile->is_active) {
            throw ValidationException::withMessages([
                'follow' => 'Solo puedes seguir emprendimientos activos.',
            ]);
        }

        $alreadyFollowing = $request->user()->favoriteEntrepreneurs()
            ->whereKey($entrepreneurProfile->id)
            ->exists();
        $request->user()->favoriteEntrepreneurs()->syncWithoutDetaching([$entrepreneurProfile->id]);

        return back()->with(
            'status',
            $alreadyFollowing ? 'Ya sigues este emprendimiento.' : 'Ahora sigues este emprendimiento.'
        );
    }

    public function destroy(Request $request, EntrepreneurProfile $entrepreneurProfile): RedirectResponse
    {
        $request->user()->favoriteEntrepreneurs()->detach($entrepreneurProfile->id);

        return back()->with('status', 'Dejaste de seguir este emprendimiento.');
    }
}
