<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends ApiController
{
    /**
     * Get paginated notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 50);
        
        $notifications = Auth::user()->notifications()->paginate($perPage);

        return $this->successResponse('Notifications retrieved successfully.', parameters: ['data' => $notifications]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id): JsonResponse
    {
        $notification = Auth::user()->notifications()->find($id);

        if (!$notification) {
            return $this->errorResponse('Notification not found.', 404);
        }

        $notification->markAsRead();

        return $this->successResponse('Notification marked as read.', parameters: ['data' => $notification]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return $this->successResponse('All notifications marked as read.');
    }
}
