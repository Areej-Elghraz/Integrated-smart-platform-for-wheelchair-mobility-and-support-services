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
            'user_id' => 'required|exists:users,id',
            'connection_type' => 'required|in:companion,doctor',
        ]);

        $sender = $request->user();
        $receiver = User::find($request->user_id);
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

        ConnectionRequest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'connection_type' => $type,
            'status' => 'pending',
        ]);

        // Send notification to receiver (could be a specific ConnectionRequestNotification)
        // For now we assume a generic or similar behavior to FriendRequest
        $receiver->notify(new \App\Notifications\DatabaseNotification\ConnectionRequestReceivedNotification($sender, $type));

        return $this->successResponse('Connection request sent successfully.', 201);
    }

    public function handleRequest(Request $request, ConnectionRequest $connectionRequest)
    {
        $request->validate(['action' => 'required|in:accept,reject']);
        $user = $request->user();

        if ($connectionRequest->receiver_id !== $user->id) {
            return $this->errorResponse('Unauthorized.', 403);
        }

        if ($connectionRequest->status !== 'pending') {
            return $this->errorResponse('Request is already processed.', 400);
        }

        if ($request->action === 'accept') {
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
                } catch (\Exception $e) {}

                return $this->successResponse('Connection request accepted.');
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->errorResponse('Error accepting request: ' . $e->getMessage(), 500);
            }
        } else {
            $connectionRequest->update(['status' => 'rejected']);
            return $this->successResponse('Connection request rejected.');
        }
    }

    public function indexPending(Request $request)
    {
        $user = $request->user();
        $pending = ConnectionRequest::with('sender')->where('receiver_id', $user->id)->where('status', 'pending')->get();
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
}
