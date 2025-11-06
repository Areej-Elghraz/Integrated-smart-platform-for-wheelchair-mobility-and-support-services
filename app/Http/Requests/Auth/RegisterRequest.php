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
        return [
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|confirmed|string|min:6',
            'policies' => 'required|boolean',
            'url'      => 'required|url',
            // 'phone'    => 'nullable|string|max:100',
            // 'avatar'   => 'nullable|image|mimes:png,jpg,jpeg',
        ];
    }
}
