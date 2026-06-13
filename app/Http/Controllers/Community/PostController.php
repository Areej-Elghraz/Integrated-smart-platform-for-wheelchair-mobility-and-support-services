<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Community\StorePostRequest;
use App\Http\Requests\Community\UpdatePostRequest;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\Request;

class PostController extends ApiController
{
    protected $postService;
    protected $interactionService;

    public function __construct(PostService $postService, \App\Services\InteractionService $interactionService)
    {
        $this->postService = $postService;
        $this->interactionService = $interactionService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['user_id', 'content', 'search', 'created_from', 'created_to', 'sort_by', 'sort_direction', 'pagination']);
        $with = ['user:id,name,image', 'sharedPost.user:id,name,image'];

        $posts = $this->postService->getPosts($request->user('sanctum'), $filters, $with);

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => 'Posts']),
            status: 200,
            parameters: $posts
        );
    }

    public function show(Request $request, Post $post)
    {
        $data = $this->postService->getPost($post);
        $data['comments'] = $this->interactionService->getComments($post, $request->only(['sort_by', 'sort_direction', 'pagination']));

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.post.singular')]),
            status: 200,
            parameters: $data
        );
    }

    public function store(StorePostRequest $request)
    {
        $this->authorize('create', Post::class);

        $images = [];
        if ($request->hasFile('images')) {
            $imageFiles = $request->file('images');
            if (!is_array($imageFiles)) {
                $imageFiles = [$imageFiles];
            }
            foreach ($imageFiles as $file) {
                $images[] = $file->store('posts/images', 'public');
            }
        }

        $files = [];
        if ($request->hasFile('files')) {
            $uploadedFiles = $request->file('files');
            if (!is_array($uploadedFiles)) {
                $uploadedFiles = [$uploadedFiles];
            }
            foreach ($uploadedFiles as $file) {
                $files[] = $file->store('posts/files', 'public');
            }
        }

        $post = $this->postService->createPost(
            $request->user(),
            $request->validated(),
            $images,
            $files
        );

        broadcast(new \App\Events\PostCreated($post));

        return $this->successResponse(
            message: __('messages.actions.created_success', ['resource' => 'Post']),
            status: 201,
            parameters: $post->load('user:id,name,image')->toArray()
        );
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $post = $this->postService->updatePost($post, $request->validated());

        return $this->successResponse(
            message: __('messages.actions.updated_success', ['resource' => 'Post']),
            status: 200,
            parameters: (new \App\Http\Resources\PostResource($post))->resolve()
        );
    }

    public function destroy(Request $request, Post $post)
    {
        $this->authorize('delete', $post);

        $this->postService->deletePost($post);

        return $this->successResponse(
            message: __('messages.actions.deleted_success', ['resource' => 'Post']),
            status: 200
        );
    }

    public function share(\App\Http\Requests\Community\SharePostRequest $request, Post $post)
    {
        $newPost = $this->postService->sharePost($request->user(), $post, $request->validated());

        return $this->successResponse(
            message: __('messages.actions.created_success', ['resource' => 'Shared Post']),
            status: 201,
            parameters: $newPost->load(['user:id,name,image', 'sharedPost.user:id,name,image'])->toArray()
        );
    }

    public function hide(Request $request, Post $post)
    {
        // Require authentication if standard api auth covers it, though standard routes group handles it
        $isHidden = $this->postService->toggleHidePost($request->user(), $post);

        $message = $isHidden
            ? __('messages.actions.created_success', ['resource' => 'Hide Post'])
            : __('messages.actions.deleted_success', ['resource' => 'Hide Post']);

        return $this->successResponse(
            message: $message,
            status: 200,
            parameters: ['is_hidden' => $isHidden]
        );
    }

    public function likes(Post $post, Request $request)
    {
        $likes = $post->likes()->with('user:id,name,image')->cursorPaginate($request->get('pagination', 15));
        $users = $likes->getCollection()->map(fn($like) => $like->user);

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.like.plural') ?? 'Users']),
            status: 200,
            parameters: [
                'likes'       => $users,
                'next_cursor' => $likes->nextCursor()?->encode(),
                'prev_cursor' => $likes->previousCursor()?->encode(),
            ]
        );
    }

    public function shares(Post $post, Request $request)
    {
        $shares = $post->sharedPosts()->with('user:id,name,image')->cursorPaginate($request->get('pagination', 15));
        $users = $shares->getCollection()->map(fn($share) => $share->user);

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.share.plural') ?? 'Users']),
            status: 200,
            parameters: [
                'shares' => $users,
                'next_cursor' => $shares->nextCursor()?->encode(),
                'prev_cursor' => $shares->previousCursor()?->encode(),
            ]
        );
    }
}