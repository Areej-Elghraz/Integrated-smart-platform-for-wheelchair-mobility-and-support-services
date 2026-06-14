<?php

namespace App\Http\Requests\Place;

use Illuminate\Foundation\Http\FormRequest;

class FilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'organization_id' => 'sometimes|nullable|exists:organizations,id',
            'category_id'     => 'sometimes|nullable|exists:categories,id',
            // 'owner_id'     => 'sometimes|nullable|exists:users,id',
            // Date range
            'created_from'    => 'sometimes|date',
            'created_to'      => 'sometimes|date|after_or_equal:created_from',
            // Sorting
            'sort_by'         => 'sometimes|string|in:name,created_at',
            'sort_direction'  => 'sometimes|string|in:asc,desc',
            // Pagination
            'pagination'      => 'sometimes|integer|min:1|max:100',
            'limit'           => 'sometimes|integer|min:1|max:100',
            // Search
            'search'          => 'sometimes|nullable|string|max:255',
        ];
    }
}
