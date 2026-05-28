<?php

namespace App\Http\Requests\Community;

use Illuminate\Foundation\Http\FormRequest;

class SendFriendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                'different:' . auth()->id(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.different' => 'You cannot send a friend request to yourself.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'user_id' => ['description' => 'The ID of the user to send a friend request to.', 'example' => 42],
        ];
    }
}
