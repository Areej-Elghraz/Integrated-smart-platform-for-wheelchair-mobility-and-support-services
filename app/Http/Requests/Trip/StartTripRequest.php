<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

class StartTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => 'required|string|in:autonomous,manual',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'mode' => ['description' => 'Trip mode. Options: autonomous, manual.', 'example' => 'autonomous'],
        ];
    }
}
