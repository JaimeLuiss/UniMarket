<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\EntrepreneurProfile;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\EntrepreneurUpdateNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ListingController extends Controller
{
    private const MAX_IMAGES = 5;

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'type' => ['nullable', Rule::in(Listing::TYPES)],
            'availability' => ['nullable', Rule::in(Listing::AVAILABILITY_TYPES)],
            'status' => ['nullable', Rule::in(Listing::STATUSES)],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'price_asc', 'price_desc'])],
        ]);

        $query = $this->publicListings()
            ->with(['user', 'category', 'images', 'entrepreneurProfile'])
            ->withCount('favoritedBy')
            ->when($request->user(), fn (Builder $query) => $query->withExists([
                'favoritedBy as is_favorited' => fn (Builder $favorites) => $favorites->whereKey($request->user()->id),
            ]))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['category'] ?? null, fn (Builder $query, int $category) => $query->where('category_id', $category))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('listing_type', $type))
            ->when($filters['availability'] ?? null, fn (Builder $query, string $availability) => $query->where('availability_type', $availability))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status));

        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->orderBy('published_at')->orderBy('id'),
            'price_asc' => $query->whereNotNull('price')->orderBy('price')->orderByDesc('published_at'),
            'price_desc' => $query->whereNotNull('price')->orderByDesc('price')->orderByDesc('published_at'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };

        return view('marketplace.index', [
            'listings' => $query->paginate(12)->withQueryString(),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, Listing $listing): View
    {
        abort_unless($this->publicListings()->whereKey($listing->id)->exists(), 404);

        $listing->load([
            'user',
            'category',
            'entrepreneurProfile',
            'images' => fn ($query) => $query->orderBy('sort_order'),
        ]);
        $listing->loadCount('favoritedBy');
        if ($requestUser = request()->user()) {
            $listing->setAttribute(
                'is_favorited',
                $requestUser->favorites()->whereKey($listing->id)->exists()
            );
        }

        $existingConversation = null;
        if ($request->user()->id !== $listing->user_id) {
            $existingConversation = $request->user()->conversationsAsBuyer()
                ->where('listing_id', $listing->id)
                ->where('seller_id', $listing->user_id)
                ->first();
        }

        $otherListings = $this->publicListings()
            ->where('user_id', $listing->user_id)
            ->whereKeyNot($listing->id)
            ->with(['user', 'category', 'images', 'entrepreneurProfile'])
            ->withCount('favoritedBy')
            ->when(auth()->user(), fn (Builder $query) => $query->withExists([
                'favoritedBy as is_favorited' => fn (Builder $favorites) => $favorites->whereKey(auth()->id()),
            ]))
            ->latest('published_at')
            ->limit(4)
            ->get();

        return view('marketplace.show', compact('listing', 'otherListings', 'existingConversation'));
    }

    public function create(): View
    {
        return view('marketplace.create', [
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'entrepreneurProfiles' => $this->ownActiveProfiles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->listingRules());
        $uploads = $request->file('images', []);
        $this->validateImageCount(count($uploads));

        $paths = $this->storeImages($uploads, $request->user()->id);

        try {
            $listing = DB::transaction(function () use ($validated, $request, $paths): Listing {
                $listing = $request->user()->listings()->create([
                    'category_id' => $validated['category_id'],
                        'entrepreneur_profile_id' => $validated['entrepreneur_profile_id'] ?? null,
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'price' => $validated['listing_type'] === Listing::TYPE_SALE ? $validated['price'] : null,
                    'listing_type' => $validated['listing_type'],
                    'availability_type' => $validated['availability_type'],
                    'status' => Listing::STATUS_AVAILABLE,
                    'condition' => $validated['condition'] ?? null,
                    'stock' => $validated['availability_type'] === Listing::AVAILABILITY_DAILY
                        ? ($validated['stock'] ?? null)
                        : null,
                    'campus_location' => $validated['campus_location'] ?? null,
                    'is_active' => true,
                    'published_at' => now(),
                ]);

                foreach ($paths as $sortOrder => $path) {
                    $listing->images()->create([
                        'path' => $path,
                        'alt_text' => $listing->title,
                        'sort_order' => $sortOrder,
                    ]);
                }

                return $listing;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($paths);

            throw $exception;
        }

        if ($listing->entrepreneur_profile_id !== null) {
            $this->notifyEntrepreneurFollowers(
                $listing,
                EntrepreneurUpdateNotification::TYPE_NEW_LISTING
            );
        }

        return redirect()->route('marketplace.show', $listing)->with('status', 'Tu publicación ya está en UniMarket.');
    }

    public function edit(Listing $listing): View
    {
        Gate::authorize('update', $listing);

        return view('marketplace.edit', [
            'listing' => $listing->load('images'),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'entrepreneurProfiles' => $this->ownActiveProfiles(),
        ]);
    }

    public function update(Request $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $validated = $request->validate($this->listingRules(allowZeroStock: true));
        $uploads = $request->file('images', []);
        $imagesToDelete = $this->validatedImageIds($request, $listing);
        $remainingImages = $listing->images()->whereNotIn('id', $imagesToDelete)->count();
        $this->validateImageCount($remainingImages + count($uploads));

        $paths = $this->storeImages($uploads, $request->user()->id);
        $deletedPaths = [];

        try {
            DB::transaction(function () use ($validated, $listing, $paths, $imagesToDelete, &$deletedPaths): void {
                $status = $listing->status;

                if ($validated['availability_type'] === Listing::AVAILABILITY_DAILY) {
                    if (! in_array($status, [Listing::STATUS_AVAILABLE, Listing::STATUS_SOLD_OUT_TODAY], true)) {
                        $status = Listing::STATUS_AVAILABLE;
                    }

                    if (isset($validated['stock']) && (int) $validated['stock'] === 0) {
                        $status = Listing::STATUS_SOLD_OUT_TODAY;
                    }
                } elseif (! in_array($status, [
                    Listing::STATUS_AVAILABLE,
                    Listing::STATUS_RESERVED,
                    Listing::STATUS_SOLD,
                    Listing::STATUS_EXCHANGED,
                ], true)) {
                    $status = Listing::STATUS_AVAILABLE;
                }

                $listing->update([
                    'category_id' => $validated['category_id'],
                    'entrepreneur_profile_id' => $validated['entrepreneur_profile_id'] ?? null,
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'price' => $validated['listing_type'] === Listing::TYPE_SALE ? $validated['price'] : null,
                    'listing_type' => $validated['listing_type'],
                    'availability_type' => $validated['availability_type'],
                    'status' => $status,
                    'condition' => $validated['condition'] ?? null,
                    'stock' => $validated['availability_type'] === Listing::AVAILABILITY_DAILY
                        ? ($validated['stock'] ?? null)
                        : null,
                    'campus_location' => $validated['campus_location'] ?? null,
                ]);

                if ($imagesToDelete !== []) {
                    $images = $listing->images()->whereIn('id', $imagesToDelete)->get();
                    $deletedPaths = $images->pluck('path')->all();
                    $listing->images()->whereIn('id', $imagesToDelete)->delete();
                }

                $sortOrder = (int) $listing->images()->max('sort_order') + 1;

                foreach ($paths as $path) {
                    $listing->images()->create([
                        'path' => $path,
                        'alt_text' => $listing->title,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($paths);

            throw $exception;
        }

        Storage::disk('public')->delete($deletedPaths);

        return redirect()->route('marketplace.show', $listing)->with('status', 'Tu publicación fue actualizada.');
    }

    public function destroy(Listing $listing): RedirectResponse
    {
        Gate::authorize('delete', $listing);

        $paths = $listing->images()->pluck('path')->all();
        DB::transaction(fn () => $listing->delete());
        Storage::disk('public')->delete($paths);

        return redirect()->route('my-listings')->with('status', 'La publicación fue eliminada.');
    }

    public function mine(Request $request): View
    {
        $listings = $request->user()->listings()
            ->with(['category', 'images'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('marketplace.mine', compact('listings'));
    }

    public function updateAvailability(Request $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $becameAvailable = DB::transaction(function () use ($request, $listing): bool {
            $lockedListing = Listing::query()->lockForUpdate()->findOrFail($listing->id);
            Gate::authorize('update', $lockedListing);

            $isDaily = $lockedListing->availability_type === Listing::AVAILABILITY_DAILY;
            $allowedStatuses = $isDaily
                ? [Listing::STATUS_AVAILABLE, Listing::STATUS_SOLD_OUT_TODAY]
                : [
                    Listing::STATUS_AVAILABLE,
                    Listing::STATUS_RESERVED,
                    Listing::STATUS_SOLD,
                    Listing::STATUS_EXCHANGED,
                ];
            $validated = $request->validate([
                'status' => ['required', Rule::in($allowedStatuses)],
                'stock' => [
                    Rule::prohibitedIf(! $isDaily),
                    Rule::requiredIf($isDaily && $lockedListing->stock !== null),
                    'nullable',
                    'integer',
                    'min:0',
                ],
            ]);

            if ($isDaily && $lockedListing->stock !== null) {
                $stock = (int) ($validated['stock'] ?? $lockedListing->stock);

                if ($validated['status'] === Listing::STATUS_AVAILABLE && $stock === 0) {
                    throw ValidationException::withMessages([
                        'stock' => 'No puedes marcar disponible una publicación con inventario en cero.',
                    ]);
                }

                $lockedListing->stock = $stock;
            }

            $previousStatus = $lockedListing->status;
            $lockedListing->status = $validated['status'];
            $lockedListing->save();

            return $isDaily
                && $validated['status'] === Listing::STATUS_AVAILABLE
                && $previousStatus === Listing::STATUS_SOLD_OUT_TODAY
                && $lockedListing->is_active
                && $lockedListing->entrepreneur_profile_id !== null;
        });

        if ($becameAvailable) {
            $this->notifyEntrepreneurFollowers(
                $listing->fresh(),
                EntrepreneurUpdateNotification::TYPE_RESTOCKED
            );
        }

        return back()->with('status', 'La disponibilidad fue actualizada.');
    }

    public function destroyImage(Listing $listing, int $image): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $listingImage = $listing->images()->findOrFail($image);
        $path = $listingImage->path;

        DB::transaction(fn () => $listingImage->delete());
        Storage::disk('public')->delete($path);

        return back()->with('status', 'La fotografía fue eliminada.');
    }

    private function publicListings(): Builder
    {
        return Listing::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->whereHas('user', fn (Builder $query) => $query->whereNotNull('email_verified_at'))
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
            ->where(function (Builder $query): void {
                $query->whereNull('entrepreneur_profile_id')
                    ->orWhereHas('entrepreneurProfile', fn (Builder $profile) => $profile->where('is_active', true));
            });
    }

    private function listingRules(bool $allowZeroStock = false): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'entrepreneur_profile_id' => [
                'nullable',
                'integer',
                Rule::exists('entrepreneur_profiles', 'id')
                    ->where('user_id', request()->user()->id)
                    ->where('is_active', true),
            ],
            'listing_type' => ['required', Rule::in(Listing::TYPES)],
            'price' => [
                Rule::requiredIf(fn () => request()->input('listing_type') === Listing::TYPE_SALE),
                'nullable',
                'numeric',
                'gt:0',
                'max:9999999999.99',
            ],
            'availability_type' => ['required', Rule::in(Listing::AVAILABILITY_TYPES)],
            'condition' => ['nullable', Rule::in([Listing::CONDITION_NEW, Listing::CONDITION_USED])],
            'stock' => [
                Rule::prohibitedIf(fn () => request()->input('availability_type') === Listing::AVAILABILITY_UNIQUE),
                'nullable',
                'integer',
                'min:'.($allowZeroStock ? 0 : 1),
            ],
            'campus_location' => ['nullable', 'string', 'max:255'],
            'images' => ['sometimes', 'array', 'max:'.self::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, EntrepreneurProfile>
     */
    private function ownActiveProfiles()
    {
        return request()->user()->entrepreneurProfile()
            ->where('is_active', true)
            ->get();
    }

    private function notifyEntrepreneurFollowers(Listing $listing, string $eventType): void
    {
        $profile = EntrepreneurProfile::query()
            ->whereKey($listing->entrepreneur_profile_id)
            ->where('is_active', true)
            ->first();

        if (! $profile) {
            return;
        }

        $profile->followers()
            ->whereKeyNot($profile->user_id)
            ->each(fn (User $follower) => $follower->notify(
                new EntrepreneurUpdateNotification($profile, $listing, $eventType)
            ));
    }

    private function validateImageCount(int $count): void
    {
        if ($count > self::MAX_IMAGES) {
            throw ValidationException::withMessages([
                'images' => 'Puedes adjuntar hasta 5 fotografías por publicación.',
            ]);
        }
    }

    /**
     * @param  array<int, UploadedFile>  $uploads
     * @return array<int, string>
     */
    private function storeImages(array $uploads, int $userId): array
    {
        $paths = [];

        foreach ($uploads as $upload) {
            $path = $upload->store('listings/'.$userId, 'public');

            if ($path === false) {
                Storage::disk('public')->delete($paths);

                throw new \RuntimeException('No se pudo guardar una de las fotografías.');
            }

            $paths[] = $path;
        }

        return $paths;
    }

    /**
     * @return array<int, int>
     */
    private function validatedImageIds(Request $request, Listing $listing): array
    {
        $validated = $request->validate([
            'delete_images' => ['sometimes', 'array'],
            'delete_images.*' => ['integer', Rule::exists('listing_images', 'id')->where('listing_id', $listing->id)],
        ]);

        return $validated['delete_images'] ?? [];
    }
}
