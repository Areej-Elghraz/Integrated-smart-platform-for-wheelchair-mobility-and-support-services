<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDataRequest extends FormRequest
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
        return [
            'name'                 => 'sometimes|required|string|max:255',
            'email'                => 'sometimes|required|email|unique:users,email,' . auth('sanctum')->id(),
            'logout_other_devices' => 'sometimes|required|boolean',
            // user
            'phone'                => 'sometimes|required|phone:AUTO,MOBILE|unique:users,phone,' . auth('sanctum')->id(), /// phone validation
            'gender'               => 'sometimes|required|string|in:male,female',
            'birth_date'           => 'sometimes|required|date|before:today',
            'weight'               => 'sometimes|required|numeric|min:1',
            'height'               => 'sometimes|required|numeric|min:1',
            'medical_condition_ids'=> 'sometimes|array',
            'medical_condition_ids.*'=> 'exists:medical_conditions,id',
            // organization
            'location'             => 'sometimes|required|string',
            'image'                => 'sometimes|required|image|mimes:png,jpg,jpeg,gif|max:2048',
        ];
    }
}
