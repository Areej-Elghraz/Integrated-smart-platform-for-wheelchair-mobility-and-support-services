<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|string|min:6',
            'language' => ['sometimes', new \Illuminate\Validation\Rules\Enum(\App\Enums\LanguagePreferenceEnum::class)],
            'role' => 'required|string|in:user,companion,doctor,organization,organization_admin',
            'phone' => 'required_if:role,user,companion,doctor|nullable|phone:AUTO,MOBILE|unique:users,phone',

            // user role specific
            'gender' => 'required_if:role,user|string|in:male,female',
            'birth_date' => 'required_if:role,user|date|before:today',
            'weight' => 'required_if:role,user|numeric|min:1',
            'height' => 'required_if:role,user|numeric|min:1',
            'doctor_username' => 'sometimes|nullable|string|exists:users,username',
            'medical_condition_ids' => 'sometimes|array',
            'medical_condition_ids.*' => 'exists:medical_conditions,id',

            // companion role specific
            'target_username' => 'required_if:role,companion|string|exists:users,username',

            // organization role specific
            'latitude' => 'required_if:role,organization|numeric|between:-90,90',
            'longitude' => 'required_if:role,organization|numeric|between:-180,180',
            'country_name' => 'sometimes|nullable|string|max:255',
            'city_name' => 'sometimes|nullable|string|max:255',
            'country_id' => 'sometimes|nullable|exists:countries,id',
            'city_id' => 'sometimes|nullable|exists:cities,id',
            'category_id' => 'sometimes|nullable|required_if:role,organization|exists:categories,id',
            'category_name' => 'sometimes|required_if:role,organization|required_without:category_id|string|max:255',
            'image' => 'required_if:role,organization|image|mimes:png,jpg,jpeg,gif|max:2048',
            'description' => 'sometimes|nullable|string',
        ];

        if ($this->input('role') === 'organization') {
            $rules['country_name'] = 'required_without:country_id|string|max:255';
            $rules['city_name'] = 'required_without:city_id|string|max:255';
            $rules['country_id'] = 'required_without:country_name|exists:countries,id';
            $rules['city_id'] = 'required_without:city_name|exists:cities,id';
        }

        return $rules;
    }
}
