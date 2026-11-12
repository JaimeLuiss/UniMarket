<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EntrepreneurProfile;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $metrics = [
            'users' => User::query()->count(),
            'listings' => Listing::query()->count(),
            'availableListings' => Listing::query()
                ->where('is_active', true)
                ->where('status', Listing::STATUS_AVAILABLE)
                ->whereNotNull('published_at')
                ->where(fn ($query) => $query->whereNull('stock')->orWhere('stock', '>', 0))
                ->whereHas('user', fn ($query) => $query->whereNotNull('email_verified_at'))
                ->whereHas('category', fn ($query) => $query->where('is_active', true))
                ->where(fn ($query) => $query->whereNull('entrepreneur_profile_id')
                    ->orWhereHas('entrepreneurProfile', fn ($profile) => $profile->where('is_active', true)))
                ->count(),
            'activeEntrepreneurs' => EntrepreneurProfile::query()->where('is_active', true)->count(),
            'pendingReports' => Report::query()->where('status', Report::STATUS_PENDING)->count(),
            'resolvedReports' => Report::query()->where('status', Report::STATUS_RESOLVED)->count(),
        ];

        $recentListings = Listing::query()
            ->with(['user:id,name', 'category:id,name'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $recentReports = Report::query()
            ->with(['reporter:id,name', 'reportedUser:id,name', 'listing:id,title'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('metrics', 'recentListings', 'recentReports'));
    }
}
