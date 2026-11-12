<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20)->withQueryString();

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->findOrFail($notification);
        $record->markAsRead();

        return back()->with('status', 'La notificación fue marcada como leída.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update([
            'read_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Todas tus notificaciones fueron marcadas como leídas.');
    }
}
