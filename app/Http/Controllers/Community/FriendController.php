<?php

namespace App\Http\Controllers\Community;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Community\SendFriendRequest;
use App\Http\Requests\Community\HandleFriendRequest;
use App\Models\User;
use App\Models\Friendship;
use App\Models\Conversation;
use App\Events\FriendRequestReceived;
use App\Events\FriendRequestAccepted;
use App\Notifications\DatabaseNotification\FriendRequestReceivedNotification;
use App\Notifications\DatabaseNotification\FriendRequestAcceptedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FriendController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $search = $request->query('search');

        $friends = $user->friends()->when($search, function($q, $search) {
            $q->where('name', 'like', "%{$search}%");
        })->get();

        return $this->successResponse('Friends retrieved successfully.', parameters: ['data' => $friends]);
    }

    public function requests(Request $request): JsonResponse
    {
        $user = $request->user();
        $pendingRequests = $user->friendOf()->wherePivot('status', 'pending')->get();

        return $this->successResponse('Pending friend requests retrieved.', parameters: ['data' => $pendingRequests]);
    }

    public function send(SendFriendRequest $request): JsonResponse
    {
        $user = $request->user();
        $friendId = $request->validated()['user_id'];

        $receiver = User::find($friendId);
        if (!$receiver) {
            return $this->errorResponse('User not found.', 404);
        }

        // Limits removed. Companions and Doctors can add friends normally.

        $existing = Friendship::where(function ($q) use ($user, $friendId) {
            $q->where('user_id', $user->id)->where('friend_id', $friendId);
        })->orWhere(function ($q) use ($user, $friendId) {
            $q->where('user_id', $friendId)->where('friend_id', $user->id);
        })->first();

        if ($existing) {
            return $this->errorResponse('Friend request already exists or you are already friends.', 400);
        }

        Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $friendId,
            'status' => 'pending',
        ]);

        broadcast(new FriendRequestReceived($user, $friendId));

        $receiver->notify(new FriendRequestReceivedNotification($user));

        return $this->successResponse('Friend request sent successfully.', 201);
    }

    public function handle(HandleFriendRequest $request, User $user): JsonResponse
    {
        $action = $request->validated()['action'];
        $me = $request->user();

        // // The other user must have sent me a request
        // $friendship = Friendship::where('user_id', $user->id)
        //     ->where('friend_id', $me->id)
        //     ->where('status', 'pending')
        //     ->first();

        $friendship = Friendship::where(function ($query) use ($me, $user) {
            $query->where('user_id', $user->id)
                ->where('friend_id', $me->id);
        })->orWhere(function ($query) use ($me, $user) {
            $query->where('user_id', $me->id)
                ->where('friend_id', $user->id);
        })
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return $this->errorResponse('No pending friend request from this user.', 404);
        }

        if ($action === 'accept') {
            DB::beginTransaction();
            try {
                $friendship->update([
                    'status' => 'accepted',
                    'accepted_at' => now(),
                ]);

                // Initialize chat immediately
                Conversation::firstOrCreate([
                    'user_one_id' => min($me->id, $user->id),
                    'user_two_id' => max($me->id, $user->id),
                ]);

                DB::commit();

                broadcast(new FriendRequestAccepted($me, $user->id));
                $user->notify(new FriendRequestAcceptedNotification($me));
                if ((($me->role == UserRoleEnum::COMPANION or $me->role == UserRoleEnum::DOCTOR) and $user->role == UserRoleEnum::USER)
                    or (($user->role == UserRoleEnum::COMPANION or $user->role == UserRoleEnum::DOCTOR) and $me->role == UserRoleEnum::USER)
                ) {
                    \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\RequestAcceptedMail($user, $me));
                }

                return $this->successResponse('Friend request accepted.');
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->errorResponse('Error accepting request: ' . $e->getMessage(), 500);
            }
        } else {
            $friendship->delete();
            return $this->successResponse('Friend request rejected.');
        }
    }

    public function remove(Request $request, User $user): JsonResponse
    {
        $me = $request->user();

        $friendship = Friendship::where(function ($q) use ($me, $user) {
            $q->where('user_id', $me->id)->where('friend_id', $user->id);
        })->orWhere(function ($q) use ($me, $user) {
            $q->where('user_id', $user->id)->where('friend_id', $me->id);
        })->first();

        if (!$friendship) {
            return $this->errorResponse('Friendship not found.', 404);
        }

        $friendship->delete();

        // Audit Log
        \App\Models\AuditLog::create([
            'user_id' => $me->id,
            'action' => 'relationship_removed',
            'model_type' => 'User',
            'model_id' => $user->id,
            'details' => json_encode(['role' => $user->role]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // As per requirements: "ويقدر يلغي الصداقة ولما تتلغى الشات هتفضل زي ما عي هيكونوا لغوا الصداقة بس"
        // We do not delete the conversation here.

        return $this->successResponse('Friend removed successfully.');
    }
}
