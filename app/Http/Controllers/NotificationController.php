<?php

namespace App\Http\Controllers;

use App\Support\StoredNotification;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $notifications = $user->notifications()->paginate(20);

        $notifications->setCollection($notifications->getCollection()->map(fn ($notification): array => [
            'id' => $notification->id,
            // The link is to the reader, not to wherever the notification points.
            'url' => route('notifications.show', $notification->id),
            ...StoredNotification::render($notification->data),
            'at' => $notification->created_at->translatedFormat('j M Y, g:i A'),
            'unread' => $notification->unread(),
        ]));

        return view('notifications.index', [
            'props' => VueProps::for([
                'notifications' => $notifications->items(),
                'unreadCount' => $user->unreadNotifications()->count(),
                'readAllUrl' => route('notifications.read'),
                'notice' => session('status'),
                'pagination' => $notifications->hasPages() ? (string) $notifications->links() : '',
            ]),
        ]);
    }

    /**
     * Open one notification: mark it read, then follow it to wherever it points.
     */
    public function show(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->findOrFail($notification);
        $record->markAsRead();

        return redirect($record->data['url'] ?? route('notifications.index'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Semua notifikasi ditandakan dibaca.');
    }
}
