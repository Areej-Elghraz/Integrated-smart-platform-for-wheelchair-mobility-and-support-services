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
            'battery'          => 'nullable|numeric|min:0|max:100',
            'voltage'          => 'nullable|numeric|min:0',
            'current'          => 'nullable|numeric|min:0',
            'temperature'      => 'nullable|numeric',
            'connection_state' => 'nullable|string|in:online,offline',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'battery'          => ['description' => 'Battery percentage (0-100).', 'example' => 85.5],
            'voltage'          => ['description' => 'Battery voltage reading (Volts).', 'example' => 24.0],
            'current'          => ['description' => 'Current draw in Amperes.', 'example' => 2.3],
            'temperature'      => ['description' => 'Device temperature in Celsius.', 'example' => 32.1],
            'connection_state' => ['description' => 'Connection status.', 'example' => 'online'],
        ];
    }
}
