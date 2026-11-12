<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $listings = Listing::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->whereHas('user', fn (Builder $query) => $query->whereNotNull('email_verified_at'))
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
            ->where(function (Builder $query): void {
                $query->whereNull('entrepreneur_profile_id')
                    ->orWhereHas('entrepreneurProfile', fn (Builder $profile) => $profile->where('is_active', true));
            })
            ->whereHas('favoritedBy', fn (Builder $query) => $query->whereKey(request()->user()->id))
            ->with(['user', 'category', 'images', 'entrepreneurProfile'])
            ->withCount('favoritedBy')
            ->withExists(['favoritedBy as is_favorited' => fn (Builder $query) => $query->whereKey($request->user()->id)])
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('marketplace.favorites', compact('listings'));
    }

    public function store(Request $request, Listing $listing): RedirectResponse
    {
        if ($listing->user_id === $request->user()->id) {
            throw ValidationException::withMessages([
                'favorite' => 'No puedes guardar tu propia publicación en favoritos.',
            ]);
        }

        $isEligible = Listing::query()
            ->whereKey($listing->id)
            ->where('is_active', true)
            ->where('status', Listing::STATUS_AVAILABLE)
            ->where(fn (Builder $query) => $query->whereNull('stock')->orWhere('stock', '>', 0))
            ->whereNotNull('published_at')
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
            ->where(function (Builder $query): void {
                $query->whereNull('entrepreneur_profile_id')
                    ->orWhereHas('entrepreneurProfile', fn (Builder $profile) => $profile->where('is_active', true));
            })
            ->exists();

        if (! $isEligible) {
            throw ValidationException::withMessages([
                'favorite' => 'Solo puedes guardar publicaciones activas y disponibles.',
            ]);
        }

        $alreadyFavorited = $request->user()->favorites()->whereKey($listing->id)->exists();
        $request->user()->favorites()->syncWithoutDetaching([$listing->id]);

        return back()->with(
            'status',
            $alreadyFavorited ? 'Esta publicación ya estaba en tus favoritos.' : 'Publicación agregada a tus favoritos.'
        );
    }

    public function destroy(Request $request, Listing $listing): RedirectResponse
    {
        $request->user()->favorites()->detach($listing->id);

        return back()->with('status', 'Publicación eliminada de tus favoritos.');
    }
}
