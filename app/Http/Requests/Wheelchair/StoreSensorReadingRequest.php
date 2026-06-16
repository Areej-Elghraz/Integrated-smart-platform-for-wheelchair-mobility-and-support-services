<?php

namespace App\Http\Requests\Wheelchair;

use Illuminate\Foundation\Http\FormRequest;

class StoreSensorReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'heart_rate_min'    => 'nullable|numeric|min:0|max:300',
            'heart_rate_max'    => 'nullable|numeric|min:0|max:300',
            'heart_rate_avg'    => 'nullable|numeric|min:0|max:300',
            'temperature_min'   => 'nullable|numeric|min:0|max:60',
            'temperature_max'   => 'nullable|numeric|min:0|max:60',
            'temperature_avg'   => 'nullable|numeric|min:0|max:60',
            'mpu_angle_min'     => 'nullable|numeric|min:-180|max:180',
            'mpu_angle_max'     => 'nullable|numeric|min:-180|max:180',
            'mpu_angle_avg'     => 'nullable|numeric|min:-180|max:180',
            'reading_time'      => 'required|date',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'trip_id'           => ['description' => 'Optional trip ID to associate this reading with.', 'example' => 1],
            'heart_rate_min'    => ['description' => 'Min heart rate in this window (BPM).', 'example' => 60],
            'heart_rate_max'    => ['description' => 'Max heart rate in this window (BPM).', 'example' => 85],
            'heart_rate_avg'    => ['description' => 'Avg heart rate in this window (BPM).', 'example' => 72.5],
            'temperature_min'   => ['description' => 'Min body temp in this window (°C).', 'example' => 36.5],
            'temperature_max'   => ['description' => 'Max body temp in this window (°C).', 'example' => 37.0],
            'temperature_avg'   => ['description' => 'Avg body temp in this window (°C).', 'example' => 36.7],
            'mpu_angle_min'     => ['description' => 'Min tilt angle (degrees).', 'example' => -5.0],
            'mpu_angle_max'     => ['description' => 'Max tilt angle (degrees).', 'example' => 10.0],
            'mpu_angle_avg'     => ['description' => 'Avg tilt angle (degrees).', 'example' => 2.3],
            'reading_time'      => ['description' => 'ISO 8601 timestamp for this reading window.', 'example' => '2026-05-25T12:00:00Z'],
        ];
    }
}
