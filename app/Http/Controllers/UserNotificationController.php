<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use App\Support\SafeRedirect;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, UserNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['read_at' => now()]);

        $redirectTo = SafeRedirect::internalPath($notification->url);

        return $redirectTo
            ? redirect($redirectTo)
            : back()->with('success', 'Уведомление отмечено как прочитанное.');
    }

    public function markAllRead(Request $request)
    {
        $request->user()
            ->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Все уведомления прочитаны.');
    }
}
