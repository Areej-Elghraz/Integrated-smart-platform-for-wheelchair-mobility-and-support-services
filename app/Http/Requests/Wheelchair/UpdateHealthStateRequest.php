<?php

namespace App\Http\Requests\Wheelchair;

use App\Models\Wheelchair;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHealthStateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $wheelchair = $this->route('wheelchair') ?? Wheelchair::find($this->route('id'));
        return $wheelchair && $this->user()->can('healthState', $wheelchair);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'current_temperature' => 'required|numeric',
            'current_heart_rate' => 'required|integer',
            'status' => 'required|string|in:normal,warning,critical,emergency,offline',
        ];
    }
}
