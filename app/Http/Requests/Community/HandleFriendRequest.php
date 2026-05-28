<?php

namespace App\Http\Requests\Community;

use Illuminate\Foundation\Http\FormRequest;

class HandleFriendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|string|in:accept,reject',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'action' => ['description' => 'Response to the friend request. Options: accept, reject.', 'example' => 'accept'],
        ];
    }
}
