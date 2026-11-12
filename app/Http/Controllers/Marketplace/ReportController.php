<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    private const DUPLICATE_WINDOW_DAYS = 30;

    public function store(Request $request, Listing $listing): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', Rule::in(array_keys(Report::REASON_LABELS))],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        DB::transaction(function () use ($request, $listing, $validated): void {
            $lockedListing = Listing::query()->lockForUpdate()->findOrFail($listing->id);

            if ($lockedListing->user_id === $request->user()->id) {
                throw ValidationException::withMessages([
                    'report' => 'No puedes reportar tu propia publicación.',
                ]);
            }

            $alreadyReported = $lockedListing->reports()
                ->where('reporter_id', $request->user()->id)
                ->where('created_at', '>=', now()->subDays(self::DUPLICATE_WINDOW_DAYS))
                ->exists();

            if ($alreadyReported) {
                throw ValidationException::withMessages([
                    'report' => 'Ya reportaste esta publicación recientemente.',
                ]);
            }

            $lockedListing->reports()->create([
                'reporter_id' => $request->user()->id,
                'reported_user_id' => $lockedListing->user_id,
                'reason' => $validated['reason'],
                'description' => $validated['description'],
                'status' => Report::STATUS_PENDING,
            ]);
        });

        return redirect()->route('marketplace.show', $listing)
            ->with('status', 'Gracias. Tu reporte fue enviado para revisión.');
    }
}
