<?php

namespace App\Http\Requests\Place;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => 'required|string|max:255',
            'description'        => 'nullable|string',
            'organization_id'    => 'nullable|exists:organizations,id',
            'category_id'        => 'required|exists:categories,id',
            'country_name'       => 'required_without:country_id|string|max:255',
            'city_name'          => 'required_without:city_id|string|max:255',
            'country_id'         => 'required_without:country_name|exists:countries,id',
            'city_id'            => 'required_without:city_name|exists:cities,id',
            'image'              => 'required|image|mimes:png,jpg,jpeg,gif|max:2048',
            'accessibility_data' => 'nullable|array',
            'floor_id'           => 'required|exists:floors,id',
            'map_id'             => 'required|exists:maps,id',
            'points'             => 'nullable|array',
            'x'                  => 'required|numeric',
            'y'                  => 'required|numeric',
            'z'                  => 'nullable|numeric',
            'rotation'           => 'nullable|numeric',
        ];
    }
}
