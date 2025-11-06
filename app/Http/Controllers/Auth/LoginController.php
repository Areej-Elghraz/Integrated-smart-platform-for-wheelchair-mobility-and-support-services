<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\GenerateTokensService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends ApiController
{
    public function __invoke(LoginRequest $request, GenerateTokensService $generateTokensService)
    {
        $user = User::firstWhere('email', $request->email);
        // if (!$user) {
        //     // return $this->errorResponse(message: __('validation.invalid_value', ['attribute' => 'email']));
        //     throw ValidationException::withMessages(['email' => __('validation.invalid_value', ['attribute' => 'email'])]);
        // }
        if (! Hash::check($request->password, $user->password)) {
            // return $this->errorResponse(message: __('validation.invalid_value', ['attribute' => 'password']));
            throw ValidationException::withMessages(['password' => __('validation.invalid_value', ['attribute' => 'password'])]);
        }
        if (!$user->email_verified_at) { ///middleware
            // return $this->errorResponse(message: __('auth.email_not_verified'));
            throw ValidationException::withMessages(['email' => __('auth.email_not_verified')]);
            // throw new \Exception(__('auth.email_not_verified'));
        }

        $tokens = $generateTokensService($user, $request->header('User-Agent'), $request->remember ?? false);

        return $this->successResponse(message: __('auth.login_success'), parameters: [
            'data'                      => $user,
            'access_token'              => $tokens['access_token'],
            'access_token_expires_in'   => $tokens['access_token_expiration'],
            'remember_token'            => $tokens['remember_token'],
            'remember_token_expires_in' => $tokens['remember_token_expiration'],
        ]);
    }
}
