<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\FilterRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use App\Http\Requests\Category\StoreRequest;
use App\Http\Requests\Category\UpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends ApiController
{
    use \App\Traits\LogsAdminActions;

    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function relationships($include, User $user)
    {
        $with = array_filter(explode(',', $include));
        $with = array_intersect($with, Category::ALLOWED_RELATIONS);
        $with = collect($with)->mapWithKeys(function ($relation) use ($user) {
            $access = [
                'place',
                'place.category',
                'place.organization',
            ];
            if (in_array($relation, $access)) {
                return [$relation => function ($q) use ($user) {
                    $q->accessibleBy($user);
                }];
            }
            return [$relation => fn($q) => $q];
        })->toArray();
        return $with;
    }

    public function index(FilterRequest $request)
    {
        $with = $this->relationships($request->query('include', ''), auth('sanctum')->user());

        $categories = $this->categoryService->getCategoriesTree(auth('sanctum')->user(), $request->validated(), $with);

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.category.plural')]),
            status: 200,
            parameters: $categories
        );
    }

    public function show(Request $request, $id)
    {
        $with = $this->relationships($request->query('include', ''), auth('sanctum')->user());

        $category = $this->categoryService->getCategory($id, $with);

        $this->authorize('view', $category);

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.category.singular')]),
            status: 200,
            parameters: $category->toArray()
        );
    }

    public function store(StoreRequest $request)
    {
        $this->authorize('create', [Category::class, $request->validated()]);

        $with = $this->relationships($request->query('include', ''), auth('sanctum')->user());

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('categories', 'public');
        }

        $category = $this->categoryService->createCategory(auth('sanctum')->user(), $request->validated(), $with, $path ?? null);

        $this->logAdminAction('created', $category, $request->validated());

        return $this->successResponse(
            message: __('messages.actions.created_success', ['resource' => __('messages.resources.category.singular')]),
            status: 201,
            parameters: $category
        );
    }

    public function update(UpdateRequest $request, Category $category)
    {
        $this->authorize('update', $category);

        $with = $this->relationships($request->query('include', ''), auth('sanctum')->user());

        if ($request->hasFile('image')) {
            if ($category->image && Storage::disk('public')->exists($category->getRawOriginal('image'))) {
                Storage::disk('public')->delete($category->getRawOriginal('image'));
            }
            $path = $request->file('image')->store('categories', 'public');
        }

        $category = $this->categoryService->updateCategory($category, auth('sanctum')->user(), $request->validated(), $with, $path ?? null);

        $this->logAdminAction('updated', $category, $request->validated());

        return $this->successResponse(
            message: __('messages.actions.updated_success', ['resource' => __('messages.resources.category.singular')]),
            status: 200,
            parameters: $category
        );
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $this->logAdminAction('deleted', $category);

        $this->categoryService->deleteCategory($category);

        return $this->successResponse(
            message: __('messages.actions.deleted_success', ['resource' => __('messages.resources.category.singular')]),
            status: 200
        );
    }
}
