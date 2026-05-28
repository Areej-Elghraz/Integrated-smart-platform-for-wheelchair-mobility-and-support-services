<?php

namespace App\Http\Requests\Wheelchair;

use App\Models\Wheelchair;
use Illuminate\Foundation\Http\FormRequest;

class StoreWheelchairRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Wheelchair::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'serial_number' => 'required|string|unique:wheelchairs,serial_number',
            'model_name' => 'required|string',
            'firmware_version' => 'required|string',
            'current_status' => 'required|string',
            'battery_percentage' => 'required|integer',
            'current_speed' => 'required|numeric',
            'mode' => 'required|string',
            'current_floor_id' => 'nullable|exists:floors,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'is_online' => 'required|boolean',
            'last_seen_at' => 'nullable|date',
            'sensors' => 'required|array',
            'sensors.*.sensor_type' => 'required|string',
            'sensors.*.sensor_name' => 'required|string',
            'sensors.*.firmware_version' => 'required|string',
            'sensors.*.status' => 'required|string',
            'sensors.*.last_reading_time' => 'nullable|date',
        ];
    }
}
