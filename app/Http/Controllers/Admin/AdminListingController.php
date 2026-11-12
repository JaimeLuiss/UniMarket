<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminListingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(Listing::STATUSES)],
            'active' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $listings = Listing::query()
            ->with(['user:id,name,email', 'category:id,name'])
            ->withCount('reports')
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['active'] ?? null, fn (Builder $query, string $active) => $query->where('is_active', $active === 'active'))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn (Builder $user) => $user
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.listings.index', compact('listings', 'filters'));
    }
}
