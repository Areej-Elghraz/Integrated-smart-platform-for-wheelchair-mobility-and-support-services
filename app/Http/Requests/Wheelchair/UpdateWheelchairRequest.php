<?php

namespace App\Http\Requests\Wheelchair;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWheelchairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'connection_state' => 'nullable|string|in:online,offline',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'connection_state' => ['description' => 'Connection status.', 'example' => 'online'],
        ];
    }
}
