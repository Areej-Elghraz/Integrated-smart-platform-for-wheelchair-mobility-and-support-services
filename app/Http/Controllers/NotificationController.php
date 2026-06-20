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
        
        $notifications->getCollection()->transform(function ($notification) {
            $data = $notification->data;
            // Map common keys to extract user ID
            $userId = $data['sender_id'] ?? $data['actor_id'] ?? $data['acceptor_id'] ?? ($data['user']['id'] ?? null);
            
            if ($userId) {
                $user = \App\Models\User::find($userId);
                if ($user) {
                    $data['sender_id'] = $user->id;
                    $data['sender_name'] = $user->name;
                    $data['sender_image'] = $user->image;
                    $notification->data = $data;
                }
            }
            return $notification;
        });

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