<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSentNotification;
use App\Notifications\DatabaseNotification\MessageSentDbNotification;
use Illuminate\Support\Facades\Cache;

class ChatService
{
    public function clearCache(int $userId, int $partnerId = null)
    {
        // Cache tags flush removed for database driver compatibility
        if ($partnerId) {
            // Cache tags flush removed for database driver compatibility
        }
    }

    public function getConversations(User $user, array $filters = []): array
    {
        $userId = $user->id;
        $search = $filters['search'] ?? null;
        $filter = $filters['filter'] ?? 'all';

        $cacheKey = 'conversations_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 3600, function () use ($userId, $search, $filter, $filters) {
            $query = Conversation::where(function ($q) use ($userId) {
                $q->where(function ($sub) use ($userId) {
                    $sub->where('user_one_id', $userId)->where('deleted_by_user_one', false);
                })->orWhere(function ($sub) use ($userId) {
                    $sub->where('user_two_id', $userId)->where('deleted_by_user_two', false);
                });
            })
                ->when($search, function ($q, $search) use ($userId) {
                    $q->where(function ($q) use ($search, $userId) {
                        $q->whereHas('userOne', function ($sub) use ($search, $userId) {
                            $sub->where('name', 'like', "%{$search}%")->where('id', '!=', $userId);
                        })->orWhereHas('userTwo', function ($sub) use ($search, $userId) {
                            $sub->where('name', 'like', "%{$search}%")->where('id', '!=', $userId);
                        })->orWhereHas('messages', function ($sub) use ($search) {
                            $sub->where('content', 'like', "%{$search}%");
                        });
                    });
                })
                ->with(['messages' => function ($q) use ($userId) {
                    $q->where(function ($sub) use ($userId) {
                        $sub->where(function ($inner) use ($userId) {
                            $inner->where('sender_id', $userId)->where('deleted_by_sender', false);
                        })->orWhere(function ($inner) use ($userId) {
                            $inner->where('sender_id', '!=', $userId)->where('deleted_by_receiver', false);
                        });
                    })->latest()->limit(1);
                }])
                ->with(['userOne:id,name,image', 'userTwo:id,name,image']);

            $conversations = $query->latest('updated_at')->cursorPaginate($filters['pagination'] ?? 20);

            $formattedCollection = $conversations->getCollection()->map(function ($conversation) use ($userId) {
                $conversation->unread_count = $conversation->messages()
                    ->where('sender_id', '!=', $userId)
                    ->where('is_read', false)
                    ->count();
                $conversation->partner = $conversation->user_one_id === $userId ? $conversation->userTwo : $conversation->userOne;
                unset($conversation->userOne, $conversation->userTwo);
                return $conversation;
            });

            if ($filter === 'unread') {
                $formattedCollection = $formattedCollection->filter(fn($c) => $c->unread_count > 0)->values();
            }

            $conversations->setCollection($formattedCollection);

            return [
                'chats'       => \App\Http\Resources\ConversationResource::collection($conversations->getCollection())->resolve(),
                'next_cursor' => $conversations->nextCursor()?->encode(),
                'prev_cursor' => $conversations->previousCursor()?->encode(),
            ];
        });
    }

    public function getMessages(User $user, int $partnerId, array $filters = []): array
    {
        $userId = $user->id;

        $conversation = Conversation::where(function ($query) use ($userId, $partnerId) {
            $query->where('user_one_id', $userId)->where('user_two_id', $partnerId);
        })->orWhere(function ($query) use ($userId, $partnerId) {
            $query->where('user_one_id', $partnerId)->where('user_two_id', $userId);
        })->first();

        if (!$conversation) {
            return [];
        }

        // Hide logically deleted conversation check. If partner deleted but I didn't, or vice-versa, we just check if it's there.
        // The messages themselves are restricted below.

        $unread = $conversation->messages()->where('sender_id', $partnerId)->where('is_read', false)->update(['is_read' => true]);
        if ($unread) {
            $this->clearCache($userId, $partnerId);
        }

        $cacheKey = 'messages_' . $conversation->id . '_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 3600, function () use ($conversation, $filters, $userId) {
            $paginator = $conversation->messages()
                ->where(function ($q) use ($userId) {
                    $q
                        ->where(function ($inner) use ($userId) {
                            $inner->where('sender_id', $userId)->where('deleted_by_sender', false);
                        })
                        ->orWhere(function ($inner) use ($userId) {
                            $inner->where('sender_id', '!=', $userId)->where('deleted_by_receiver', false);
                        });
                })
                ->when($filters['search'] ?? null, fn($q, $search) => $q->where('content', 'like', "%{$search}%"))
                ->latest()
                ->cursorPaginate($filters['pagination'] ?? 20);

            return [
                'messages'    => \App\Http\Resources\MessageResource::collection($paginator->getCollection())->resolve(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
            ];
        });
    }

    public function sendMessage(User $user, int $partnerId, array $data, ?object $file = null): Message
    {
        $userId = $user->id;

        $conversation = Conversation::firstOrCreate([
            'user_one_id' => min($userId, $partnerId),
            'user_two_id' => max($userId, $partnerId),
        ]);

        $type = 'text';
        $attachmentPath = null;

        if ($file) {
            $mime = $file->getMimeType();
            if (str_starts_with($mime, 'image/')) {
                $type = !empty($data['content']) ? 'text_image' : 'image';
                $attachmentPath = $file->store('chats/images', 'public');
            } elseif (str_starts_with($mime, 'audio/') || str_starts_with($mime, 'video/')) {
                $type = 'voice';
                $attachmentPath = $file->store('chats/audios', 'public');
            } else {
                $attachmentPath = $file->store('chats/files', 'public');
            }
        }

        $message = $conversation->messages()->create([
            'sender_id'  => $userId,
            'type'       => $type,
            'content'    => $data['content'] ?? null,
            'attachment' => $attachmentPath,
            'is_read'    => false,
            'deleted_by_sender' => false,
            'deleted_by_receiver' => false,
        ]);

        // Revive the conversation for both if it was marked as deleted by either
        $conversation->update([
            'deleted_by_user_one' => false,
            'deleted_by_user_two' => false,
            'updated_at'          => now(),
        ]);

        // Broadcast Event
        broadcast(new MessageSentNotification($message, $partnerId));

        // DB Notification
        $receiver = User::find($partnerId);
        if ($receiver) {
            $receiver->notify(new MessageSentDbNotification($message));
        }

        $this->clearCache($userId, $partnerId);
        return $message;
    }

    public function deleteConversation(User $user, int $partnerId, string $type = 'for_me'): void
    {
        $userId = $user->id;
        $conversation = Conversation::where(function ($query) use ($userId, $partnerId) {
            $query->where('user_one_id', $userId)->where('user_two_id', $partnerId);
        })->orWhere(function ($query) use ($userId, $partnerId) {
            $query->where('user_one_id', $partnerId)->where('user_two_id', $userId);
        })->firstOrFail();

        if ($type === 'for_both') {
            $conversation->delete();
        } else {
            if ($conversation->user_one_id === $userId) {
                $conversation->update(['deleted_by_user_one' => true]);
            } else {
                $conversation->update(['deleted_by_user_two' => true]);
            }
        }
        $this->clearCache($userId, $partnerId);
    }

    public function updateMessage(User $user, Message $message, array $data): Message
    {
        if ($message->sender_id !== $user->id) {
            abort(403, 'Unauthorized to update this message.');
        }

        $message->update([
            'content' => $data['content'] ?? null,
            'type'    => (empty($data['content']) && $message->attachment) ? 'image' : ($message->attachment ? 'text_image' : 'text'),
        ]);

        $partnerId = $message->conversation->user_one_id === $user->id
            ? $message->conversation->user_two_id
            : $message->conversation->user_one_id;

        $this->clearCache($user->id, $partnerId);
        return $message;
    }

    public function deleteMessage(User $user, Message $message, string $type = 'for_me'): void
    {
        $isSender = $message->sender_id === $user->id;

        if ($type === 'for_both') {
            if (!$isSender) {
                abort(403, 'Only the sender can delete a message for everyone.');
            }
            $message->delete();
        } else {
            if ($isSender) {
                $message->update(['deleted_by_sender' => true]);
            } else {
                $message->update(['deleted_by_receiver' => true]);
            }
        }

        $partnerId = $message->conversation->user_one_id === $user->id
            ? $message->conversation->user_two_id
            : $message->conversation->user_one_id;

        $this->clearCache($user->id, $partnerId);
    }
}
