<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\GenerateTokensService;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends ApiController
{
    public function __invoke(LoginRequest $request, GenerateTokensService $generateTokensService, UserService $userService)
    {
        // $deviceRequest = $request->input('device');

        $login = $request->input('login') ?? $request->input('email') ?? $request->input('username');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = $field === 'email' 
            ? $userService->getUserByEmail($login) 
            : \App\Models\User::where('username', $login)->first();

        if (!$user) {
            $errorField = $request->has('email') ? 'email' : ($request->has('username') ? 'username' : 'login');
            throw ValidationException::withMessages([$errorField => __('auth.failed')]);
        }

        if (!Auth::attempt([$field => $login, 'password' => $request->password], $request->remember ?? false)) {
            return $this->errorResponse(
                __('auth.failed'),
                401,
            );
        }

        $user = auth('sanctum')->user();

        if (is_null($user->email_verified_at)) {
            // Log out the user since they are not verified
            Auth::logout();
            return $this->errorResponse(
                'Please verify your email using the OTP sent to you before logging in.',
                403,
            );
        }

        // dd(auth('sanctum')->user());
        $user   = auth('sanctum')->user();
        $tokens = $generateTokensService($user, $request->remember ?? false);

        return $this->successResponse(message: __('auth.login_success'), parameters: [
            'user'                      => $user,
            'access_token'              => $tokens['access_token'],
            'access_token_expires_in'   => $tokens['access_token_expiration'],
            'remember_token'            => $tokens['remember_token'],
            'remember_token_expires_in' => $tokens['remember_token_expiration'],
        ]);
    }
}
