<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Return the latest 30 notifications + unread count.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->take(30)
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'type'       => $n->data['type'] ?? 'unknown',
                'title'      => $n->data['title'] ?? '',
                'message'    => $n->data['message'] ?? '',
                'icon'       => $n->data['icon'] ?? '🔔',
                'url'        => $n->data['url'] ?? '/dashboard',
                'meta'       => $n->data['meta'] ?? [],
                'read_at'    => $n->read_at,
                'created_at' => $n->created_at,
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a single notification as read. Returns updated unread count.
     */
    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
