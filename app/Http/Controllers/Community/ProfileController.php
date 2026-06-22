<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\ApiController;
use App\Models\User;
use Illuminate\Http\Request;

// use Illuminate\Http\Request;

class ProfileController extends ApiController
{
    public function index(Request $request)
    {
        $query = User::query();
        
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        
        $paginator = $query->cursorPaginate($request->get('pagination', 15));
        
        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => 'Community Users']),
            status: 200,
            parameters: [
                'users' => $paginator->getCollection()->map->only(['id', 'name', 'image'])->toArray(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
            ]
        );
    }

    public function show(User $user, Request $request)
    {
        $paginator = $user->posts()
            ->with(['sharedPost.user:id,name,image'])
            ->withCount(['likes', 'comments', 'sharedPosts as shares_count'])
            ->latest()
            ->cursorPaginate($request->get('pagination', 15));

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => 'Community Profile']),
            status: 200,
            parameters: [
                'user'  => $user->only(['id', 'name', 'image']),
                'posts' => \App\Http\Resources\PostResource::collection($paginator->getCollection())->resolve(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
            ]
        );
    }
}
