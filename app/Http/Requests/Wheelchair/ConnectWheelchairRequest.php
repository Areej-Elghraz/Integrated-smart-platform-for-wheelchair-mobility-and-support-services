<?php

namespace App\Http\Requests\Wheelchair;

use Illuminate\Foundation\Http\FormRequest;

class ConnectWheelchairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'serial_number' => 'required|string|max:100',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'serial_number' => [
                'description' => 'The unique serial number of the wheelchair to connect.',
                'example' => 'WC-2024-XYZ',
            ],
        ];
    }
}
