<?php

namespace App\Http\Requests\Place;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('points') && is_string($this->points)) {
            $points = json_decode($this->points, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge([
                    'points' => $points,
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'name'               => 'required|string|max:255',
            'description'        => 'nullable|string',
            'category_id'        => 'required|exists:categories,id',
            'category_name'      => 'sometimes|required_without:category_id|string|max:255',
            'image'              => 'required|image|mimes:png,jpg,jpeg,gif|max:2048',
            'floor_id'           => 'nullable|exists:floors,id',
            'points'             => 'required|array',
            'points.*.x'         => 'required|numeric',
            'points.*.y'         => 'required|numeric',
            'x'                  => 'nullable|numeric',
            'y'                  => 'nullable|numeric',
            'z'                  => 'nullable|numeric',
            'rotation'           => 'nullable|numeric',
        ];
    }
}
