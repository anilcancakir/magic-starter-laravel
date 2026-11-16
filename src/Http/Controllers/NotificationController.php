<?php

namespace AnilcanCakir\MagicStarter\Http\Controllers;

use AnilcanCakir\MagicStarter\Http\Resources\NotificationResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * Manages database notifications for the authenticated user.
 */
class NotificationController
{
    /**
     * Retrieve a paginated list of user notifications.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);

        return NotificationResource::collection(
            $request->user()->notifications()->paginate($perPage),
        );
    }

    /**
     * Get the count of unread notifications.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->json([
            'data' => [
                'count' => $count,
            ],
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $this->findNotification($request, $id);

        $notification->markAsRead();

        return response()->json([
            'data' => null,
            'message' => __('magic-starter::notifications.marked_as_read'),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return response()->json([
            'data' => null,
            'message' => __('magic-starter::notifications.all_marked_as_read'),
        ]);
    }

    /**
     * Delete a specific notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $this->findNotification($request, $id);

        $notification->delete();

        return response()->json([
            'data' => null,
            'message' => __('magic-starter::notifications.deleted'),
        ]);
    }

    /**
     * Find one of the user's notifications by its id.
     *
     * The table keys on a uuid whatever `use_uuids` says, and PostgreSQL rejects a malformed value against a uuid
     * column, so an id that is not one is a missing notification before any query.
     */
    private function findNotification(Request $request, string $id): DatabaseNotification
    {
        if (! Str::isUuid($id)) {
            throw (new ModelNotFoundException)->setModel(DatabaseNotification::class, $id);
        }

        return $request->user()->notifications()->findOrFail($id);
    }
}
