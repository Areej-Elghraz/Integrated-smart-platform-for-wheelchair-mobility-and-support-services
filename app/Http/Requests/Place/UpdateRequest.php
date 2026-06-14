<?php

namespace App\Http\Requests\Place;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'name'               => 'sometimes|string|max:255',
            'description'        => 'sometimes|string',
            'category_id'        => 'sometimes|required_without:category_name|exists:categories,id',
            'category_name'      => 'sometimes|required_without:category_id|string|max:255',
            'image'              => 'sometimes|image|mimes:png,jpg,jpeg,gif|max:2048',
            'floor_id'           => 'nullable|exists:floors,id',
            'points'             => 'sometimes|required|array',
            'points.*.x'         => 'required_with:points|numeric',
            'points.*.y'         => 'required_with:points|numeric',
            'x'                  => 'nullable|numeric',
            'y'                  => 'nullable|numeric',
            'z'                  => 'nullable|numeric',
            'rotation'           => 'nullable|numeric',
        ];
    }
}
