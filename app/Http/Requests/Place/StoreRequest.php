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
            'country_name'       => 'required_without:country_id|string|max:255',
            'city_name'          => 'required_without:city_id|string|max:255',
            'country_id'         => 'required_without:country_name|exists:countries,id',
            'city_id'            => 'required_without:city_name|exists:cities,id',
            'image'              => 'required|image|mimes:png,jpg,jpeg,gif|max:2048',
            'accessibility_data' => 'nullable|array',
            'map_id'             => 'nullable|exists:maps,id',
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
