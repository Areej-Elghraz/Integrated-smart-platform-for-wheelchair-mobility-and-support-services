<?php

namespace App\Http\Requests\Organization;

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
            'name'          => 'required|string|max:255',
            'category_id'   => 'nullable|exists:categories,id',
            'category_name' => 'required_without:category_id|string|max:255',
            'country_name'  => 'required_without:country_id|string|max:255',
            'city_name'     => 'required_without:city_id|string|max:255',
            'country_id'    => 'required_without:country_name|exists:countries,id',
            'city_id'       => 'required_without:city_name|exists:cities,id',
            'latitude'      => 'required|numeric|between:-90,90',
            'longitude'     => 'required|numeric|between:-180,180',
            'image'         => 'required|image|mimes:png,jpg,jpeg,gif|max:2048',
            'description'   => 'nullable|string',
        ];
    }
}
