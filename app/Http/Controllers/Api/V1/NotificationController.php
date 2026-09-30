<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Locales;
use App\Support\StoredNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Throwable;

/**
 * The notification bell, for the app. Each notification points at a web
 * page; the app is told what that page is about (a booking, an enquiry…)
 * so it can open its own screen instead.
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()->paginate(20);

        return response()->json([
            'data' => $notifications->getCollection()->map(function (DatabaseNotification $notification): array {
                $shown = StoredNotification::render($notification->data);

                return [
                    'id' => $notification->id,
                    'icon' => $shown['icon'],
                    'title' => $shown['title'],
                    'body' => $shown['body'] ?: null,
                    'created_at' => $notification->created_at->toIso8601String(),
                    'read' => $notification->read(),
                    'target' => self::target($shown['url']),
                ];
            })->values(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'unread' => $user->unreadNotifications()->count(),
        ]);
    }

    /** Mark the given notifications read, or all of them. */
    public function read(Request $request): JsonResponse
    {
        $ids = $request->validate(['ids' => ['sometimes', 'array', 'max:100'], 'ids.*' => ['string']])['ids'] ?? null;

        $request->user()->unreadNotifications()
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids))
            ->update(['read_at' => now()]);

        return response()->json(['unread' => $request->user()->unreadNotifications()->count()]);
    }

    /**
     * What a notification's web link is about.
     *
     * @return array{type: string|null, id: string|null}
     */
    private static function target(?string $url): array
    {
        $none = ['type' => null, 'id' => null];

        if (! $url) {
            return $none;
        }

        try {
            $route = app('router')->getRoutes()->match(Request::create($url));
        } catch (Throwable) {
            return $none;
        }

        $name = (string) Locales::baseRouteName($route->getName());

        return match (true) {
            in_array($name, ['vendor.bookings.show', 'bookings.show'], true) => ['type' => 'booking', 'id' => (string) $route->parameter('booking')],
            $name === 'vendor.enquiries.show' => ['type' => 'enquiry', 'id' => (string) $route->parameter('enquiry')],
            in_array($name, ['payments.show', 'payments.document'], true) => ['type' => 'payment', 'id' => (string) $route->parameter('payment')],
            str_starts_with($name, 'vendor.boost.') => ['type' => 'boost', 'id' => null],
            str_starts_with($name, 'vendor.pro.') => ['type' => 'pro', 'id' => null],
            default => $none,
        };
    }
}
