<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\LogoutRequest;

class LogoutController extends ApiController
{
    public function __invoke(LogoutRequest $request)
    {
        $user = $request->user();
        $deviceName = $request->header('User-Agent'); ///device name
        if ($request->has('remember') and $request->remember == 1) {
            // $user->tokens()->delete();
            $user->tokens()?->where('name', 'LIKE', '%' . $user->email)->delete();
        } else {
            $user->tokens()?->where('name', 'access_token_' . $deviceName . '_' . $user->email)
                ->orWhere('name', 'remember_token_' . $deviceName . '_' . $user->email)->delete();
        }
        return $this->successResponse(__('messages.logout_success'));
    }
}
