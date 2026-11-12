<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(Report::STATUSES)],
            'reason' => ['nullable', Rule::in(array_keys(Report::REASON_LABELS))],
            'search' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', Rule::in(['newest', 'oldest'])],
        ]);

        $query = Report::query()
            ->with([
                'reporter:id,name,email',
                'reportedUser:id,name,email',
                'listing:id,title',
            ])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['reason'] ?? null, fn (Builder $query, string $reason) => $query->where('reason', $reason))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('listing', fn (Builder $listing) => $listing->where('title', 'like', '%'.$search.'%'))
                        ->orWhereHas('reporter', fn (Builder $user) => $user
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%'))
                        ->orWhereHas('reportedUser', fn (Builder $user) => $user
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%'));
                });
            });

        $query->orderBy('created_at', ($filters['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc');

        $reports = $query->paginate(15)->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'filters' => $filters,
            'reasons' => Report::REASON_LABELS,
        ]);
    }

    public function show(Report $report): View
    {
        $report->load([
            'reporter:id,name,email,created_at',
            'reportedUser:id,name,email,created_at',
            'reviewer:id,name,email',
            'listing.category:id,name',
        ]);

        return view('admin.reports.show', [
            'report' => $report,
            'reasons' => Report::REASON_LABELS,
            'statuses' => [
                Report::STATUS_PENDING => 'Pendiente',
                Report::STATUS_REVIEWED => 'Revisado',
                Report::STATUS_RESOLVED => 'Resuelto',
                Report::STATUS_REJECTED => 'Rechazado',
            ],
        ]);
    }

    public function review(Request $request, Report $report): RedirectResponse
    {
        return $this->transition($request, $report, Report::STATUS_REVIEWED);
    }

    public function resolve(Request $request, Report $report): RedirectResponse
    {
        return $this->transition($request, $report, Report::STATUS_RESOLVED);
    }

    public function reject(Request $request, Report $report): RedirectResponse
    {
        return $this->transition($request, $report, Report::STATUS_REJECTED);
    }

    public function updateListingModeration(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate(['is_active' => ['required', 'boolean']]);
        $listing = $report->listing;

        abort_unless($listing instanceof Listing, 404);

        $listing->update(['is_active' => (bool) $validated['is_active']]);

        return back()->with('status', $listing->is_active
            ? 'La publicación fue reactivada.'
            : 'La publicación fue desactivada por moderación.');
    }

    private function transition(Request $request, Report $report, string $nextStatus): RedirectResponse
    {
        $validated = $request->validate([
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $report, $nextStatus, $validated): void {
            $lockedReport = Report::query()->lockForUpdate()->findOrFail($report->id);
            $allowedPreviousStatuses = $nextStatus === Report::STATUS_REVIEWED
                ? [Report::STATUS_PENDING]
                : [Report::STATUS_PENDING, Report::STATUS_REVIEWED];

            if (! in_array($lockedReport->status, $allowedPreviousStatuses, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Este reporte ya fue cerrado y no puede modificarse nuevamente.',
                ]);
            }

            $reviewedAt = now();
            $notes = trim($validated['resolution_notes'] ?? '');
            $entry = sprintf(
                '[%s] %s — %s%s',
                $reviewedAt->format('Y-m-d H:i:s'),
                $request->user()->name,
                $nextStatus,
                $notes === '' ? '' : ': '.$notes,
            );
            $history = trim(($lockedReport->resolution_notes ? $lockedReport->resolution_notes."\n\n" : '').$entry);

            $lockedReport->update([
                'status' => $nextStatus,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => $reviewedAt,
                'resolution_notes' => $history,
            ]);
        });

        return back()->with('status', 'La revisión del reporte fue registrada.');
    }
}
