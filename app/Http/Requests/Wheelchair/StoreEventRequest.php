<?php

namespace App\Http\Requests\Wheelchair;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'          => 'required|string|in:health,obstacle,sos,battery',
            'severity'      => 'required|string|in:low,medium,critical',
            'message'       => 'required|string|max:1000',
            'data'          => 'required|array',
            'event_source'  => 'nullable|string|in:ai,system',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'type'         => ['description' => 'Event type. Options: health, obstacle, sos, battery.', 'example' => 'health'],
            'severity'     => ['description' => 'Event severity. Options: low, medium, critical.', 'example' => 'medium'],
            'message'      => ['description' => 'Human-readable event description.', 'example' => 'Elevated heart rate detected'],
            'data'         => ['description' => 'Technical payload with event details.', 'example' => ['heart_rate' => 110]],
            'event_source' => ['description' => 'Who generated this event. Options: ai (default), system.', 'example' => 'ai'],
        ];
    }
}
