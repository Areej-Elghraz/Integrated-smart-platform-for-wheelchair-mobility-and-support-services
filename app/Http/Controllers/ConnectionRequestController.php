<?php

namespace App\Http\Controllers;

use App\Models\ConnectionRequest;
use App\Models\User;
use App\Models\Friendship;
use App\Models\Conversation;
use App\Enums\UserRoleEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConnectionRequestController extends ApiController
{
    public function sendRequest(Request $request)
    {
        $request->validate([
            'username' => 'required|exists:users,username',
            'connection_type' => 'required|in:companion,doctor',
        ]);

        $sender = $request->user();
        $receiver = User::where('username', $request->username)->first();
        $type = $request->connection_type;

        if ($sender->id === $receiver->id) {
            return $this->errorResponse('You cannot send a request to yourself.', 400);
        }

        // Logic for Companion
        if ($type === 'companion') {
            if (!$sender->isCompanion()) {
                return $this->errorResponse('Only companions can send companion requests.', 403);
            }
            if (!$receiver->isUser()) {
                return $this->errorResponse('Companion requests can only be sent to normal users.', 400);
            }
            // Companion can only follow 1 User
            $existingAccepted = ConnectionRequest::where('sender_id', $sender->id)
                ->where('connection_type', 'companion')
                ->where('status', 'accepted')
                ->exists();
            if ($existingAccepted) {
                return $this->errorResponse('A companion can only follow one user.', 400);
            }
        }
        // Logic for Doctor
        else if ($type === 'doctor') {
            if (!$sender->isUser()) {
                return $this->errorResponse('Only normal users can send doctor requests.', 403);
            }
            if (!$receiver->isDoctor()) {
                return $this->errorResponse('Doctor requests can only be sent to doctors.', 400);
            }
            // User can only have 1 Doctor
            $existingDoctor = ConnectionRequest::where('sender_id', $sender->id)
                ->where('connection_type', 'doctor')
                ->where('status', 'accepted')
                ->exists();
            if ($existingDoctor) {
                return $this->errorResponse('You already have a doctor connected.', 400);
            }
        }

        $existingRequest = ConnectionRequest::where('sender_id', $sender->id)
            ->where('receiver_id', $receiver->id)
            ->where('connection_type', $type)
            ->first();

        if ($existingRequest) {
            return $this->errorResponse('A request already exists.', 400);
        }

        $connectionRequest = ConnectionRequest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'connection_type' => $type,
            'status' => 'pending',
        ]);

        // Send notification to receiver
        $receiver->notify(new \App\Notifications\DatabaseNotification\ConnectionRequestReceivedNotification($sender, $type, $connectionRequest->id));

        return $this->successResponse('Connection request sent successfully.', 201, parameters: ['data' => $connectionRequest]);
    }

    public function handleRequest(Request $request, ConnectionRequest $connectionRequest)
    {
        $user = $request->user();

        if ($connectionRequest->receiver_id !== $user->id) {
            return $this->errorResponse('Unauthorized.', 403);
        }

        if ($connectionRequest->status !== 'pending') {
            return $this->errorResponse('Request is already processed.', 400);
        }

        DB::beginTransaction();
        try {
            $connectionRequest->update([
                'status' => 'accepted',
                'accepted_at' => now()
            ]);

            // Create a Friendship so they can chat in the community
            $existingFriendship = Friendship::where(function ($q) use ($connectionRequest) {
                $q->where('user_id', $connectionRequest->sender_id)->where('friend_id', $connectionRequest->receiver_id);
            })->orWhere(function ($q) use ($connectionRequest) {
                $q->where('user_id', $connectionRequest->receiver_id)->where('friend_id', $connectionRequest->sender_id);
            })->first();

            if (!$existingFriendship) {
                Friendship::create([
                    'user_id' => $connectionRequest->sender_id,
                    'friend_id' => $connectionRequest->receiver_id,
                    'status' => 'accepted',
                    'accepted_at' => now(),
                ]);

                Conversation::firstOrCreate([
                    'user_one_id' => min($connectionRequest->sender_id, $connectionRequest->receiver_id),
                    'user_two_id' => max($connectionRequest->sender_id, $connectionRequest->receiver_id),
                ]);
            } else if ($existingFriendship->status !== 'accepted') {
                $existingFriendship->update(['status' => 'accepted', 'accepted_at' => now()]);
            }

            DB::commit();

            // notification and email
            $sender = User::find($connectionRequest->sender_id);
            $sender->notify(new \App\Notifications\DatabaseNotification\ConnectionRequestAcceptedNotification($user, $connectionRequest->connection_type));
            try {
                \Illuminate\Support\Facades\Mail::to($sender->email)->send(new \App\Mail\RequestAcceptedMail($sender, $user));
            } catch (\Exception $e) {
            }

            return $this->successResponse('Connection request accepted.', parameters: ['data' => $connectionRequest]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error accepting request: ' . $e->getMessage(), 500);
        }
    }

    public function indexPending(Request $request)
    {
        $user = $request->user();
        $pending = ConnectionRequest::with(['sender', 'receiver'])->where('receiver_id', $user->id)->orWhere('sender_id', $user->id)->where('status', 'pending')->get();
        return $this->successResponse('Pending connection requests retrieved.', parameters: ['data' => $pending]);
    }

    public function indexConnectedCompanions(Request $request)
    {
        $user = $request->user();
        if (!$user->isUser()) {
            return $this->errorResponse('Only normal users have connected companions.', 403);
        }
        $companions = $user->connectedCompanions;
        return $this->successResponse('Connected companions retrieved.', parameters: ['data' => $companions]);
    }

    public function getConnectedDoctor(Request $request)
    {
        $user = $request->user();
        if (!$user->isUser()) {
            return $this->errorResponse('Only normal users have a connected doctor.', 403);
        }
        $doctor = $user->connectedDoctor;
        return $this->successResponse('Connected doctor retrieved.', parameters: ['data' => $doctor]);
    }

    public function removeConnection(Request $request, User $connectedUser)
    {
        $me = $request->user();

        $connection = ConnectionRequest::where(function ($q) use ($me, $connectedUser) {
            $q->where('sender_id', $me->id)->where('receiver_id', $connectedUser->id);
        })->orWhere(function ($q) use ($me, $connectedUser) {
            $q->where('sender_id', $connectedUser->id)->where('receiver_id', $me->id);
        })->first();

        if (!$connection) {
            return $this->errorResponse('Connection not found.', 404);
        }

        $connection->delete();

        // Also delete friendship if exists
        $friendship = Friendship::where(function ($q) use ($me, $connectedUser) {
            $q->where('user_id', $me->id)->where('friend_id', $connectedUser->id);
        })->orWhere(function ($q) use ($me, $connectedUser) {
            $q->where('user_id', $connectedUser->id)->where('friend_id', $me->id);
        })->first();
        if ($friendship) $friendship->delete();

        return $this->successResponse('Connection removed successfully.');
    }
}