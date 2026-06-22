<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class FavoriteFilterRequest extends FormRequest
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
            'type'            => 'sometimes|string|in:places,organizations,all',
            'pagination'      => 'sometimes|integer|min:1|max:100',
            'limit'           => 'sometimes|integer|min:1|max:100',
        ];
    }
}
