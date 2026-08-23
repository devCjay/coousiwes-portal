<?php

namespace App\Http\Controllers;

use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationCenterController extends Controller
{
    public function index(Request $request): View
    {
        return view('pages.notifications.index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'notifications' => $request->user()
                ->unreadNotifications()
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (DatabaseNotification $notification): array => $this->serialize($notification))
                ->values(),
        ]);
    }

    public function markRead(Request $request, DatabaseNotification $notification): JsonResponse|RedirectResponse
    {
        abort_unless($notification->notifiable_type === $request->user()::class && (int) $notification->notifiable_id === (int) $request->user()->id, 403);

        $notification->markAsRead();

        return AjaxResponse::success($request, 'Notification marked as read.');
    }

    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return AjaxResponse::success($request, 'All notifications marked as read.', reload: true);
    }

    /**
     * @return array{id: string, title: string, message: string, tone: string, action_url: mixed, created_at: string|null}
     */
    private function serialize(DatabaseNotification $notification): array
    {
        return [
            'id' => (string) $notification->id,
            'title' => (string) ($notification->data['title'] ?? 'Portal notification'),
            'message' => (string) ($notification->data['message'] ?? ''),
            'tone' => (string) ($notification->data['tone'] ?? 'info'),
            'action_url' => $notification->data['action_url'] ?? null,
            'created_at' => $notification->created_at?->diffForHumans(),
        ];
    }
}
