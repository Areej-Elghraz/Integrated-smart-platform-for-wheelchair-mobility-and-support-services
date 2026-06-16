<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovementStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('position') && is_string($this->position)) {
            $position = json_decode($this->position, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge([
                    'position' => $position,
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'movement_status' => 'required|string|in:moving,idle',
            'speed' => 'required|numeric',
            'position' => 'required|array',
            'position.x' => 'required|numeric',
            'position.y' => 'required|numeric',
            'theta' => 'required|numeric',
            'mode' => 'required|string|in:autonomous,manual',
            'risk_level' => 'required|string|in:low,medium,high',
            'obstacle_detected' => 'required|boolean',
            'obstacle_distance' => 'required|numeric',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'movement_status'   => ['description' => 'Current movement state. Options: moving, idle.', 'example' => 'moving'],
            'speed'             => ['description' => 'Speed in m/s.', 'example' => 1.2],
            'position'          => ['description' => 'Current position coordinates.', 'example' => ['x' => 10.5, 'y' => 20.3]],
            'theta'             => ['description' => 'Orientation angle in degrees.', 'example' => 45.0],
            'mode'              => ['description' => 'Movement mode. Options: autonomous, manual.', 'example' => 'autonomous'],
            'risk_level'        => ['description' => 'Current risk assessment. Options: low, medium, high.', 'example' => 'low'],
            'obstacle_detected' => ['description' => 'Whether an obstacle is currently detected.', 'example' => false],
            'obstacle_distance' => ['description' => 'Distance to closest obstacle in meters.', 'example' => 2.5],
        ];
    }
}
