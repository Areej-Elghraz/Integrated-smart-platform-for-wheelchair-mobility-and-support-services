<?php

namespace App\Http\Requests\Auth;

use App\Enums\DeviceTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class LoginRequest extends FormRequest
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
            'email'       => 'required_without_all:username,login|string',
            'username'    => 'required_without_all:email,login|string',
            'login'       => 'required_without_all:email,username|string',
            'password'    => 'required|string|min:6',
            'remember'    => 'sometimes|boolean',
            // 'device'      => 'required|array',
            // 'device.id'   => 'required|string',
            // 'device.name' => 'required|string',
            // 'device.os' => ['required', new Enum(DeviceTypeEnum::class)], // add DeviceTypeEnum
        ];
    }
}
