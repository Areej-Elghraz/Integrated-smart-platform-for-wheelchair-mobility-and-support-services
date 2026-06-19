<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'parent_id'       => 'nullable|exists:categories,id',
            'organization_id' => 'nullable|exists:organizations,id',
            // 'image'           => 'required|image|mimes:png,jpg,jpeg,gif|max:2048',
        ];
    }
}
