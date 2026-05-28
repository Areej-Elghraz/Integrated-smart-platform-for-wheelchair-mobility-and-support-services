<?php

namespace App\Http\Requests\Wheelchair;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVitalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'heart_rate'          => 'required|numeric|min:0|max:300',
            'heart_rate_status'   => 'required|string|in:normal,medium,warning',
            'temperature'         => 'required|numeric|min:0|max:60',
            'temperature_status'  => 'required|string|in:normal,medium,warning',
            'mpu_angle'           => 'required|numeric|min:-180|max:180',
            'fall_status'         => 'required|boolean',
            'type'                => 'nullable|string|max:50',
            'risk_level'          => 'required|string|in:low,medium,high',
            'reason'              => 'nullable|string|max:500',
            'recommendation'      => 'nullable|string|max:500',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'heart_rate'         => ['description' => 'Heart rate in BPM.', 'example' => 72],
            'heart_rate_status'  => ['description' => 'Status of heart rate. Options: normal, medium, warning.', 'example' => 'normal'],
            'temperature'        => ['description' => 'Body temperature in Celsius.', 'example' => 36.7],
            'temperature_status' => ['description' => 'Status of body temperature. Options: normal, medium, warning.', 'example' => 'normal'],
            'mpu_angle'          => ['description' => 'Tilt angle from MPU sensor in degrees.', 'example' => 5.2],
            'fall_status'        => ['description' => 'Whether a fall has been detected.', 'example' => false],
            'risk_level'         => ['description' => 'Overall risk level. Options: low, medium, high.', 'example' => 'low'],
            'reason'             => ['description' => 'Optional explanation for the risk level.', 'example' => 'Elevated heart rate detected'],
            'recommendation'     => ['description' => 'Optional action recommendation.', 'example' => 'Monitor closely for 5 minutes'],
        ];
    }
}
