<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\GenerateCodeService;

class RegisterController extends ApiController
{
    public function __invoke(RegisterRequest $request, GenerateCodeService $generateCodeService)
    {
        $user = User::create($request->validated());
        // $user->email_verified_at = now();
        $generateCodeService->verificationCode($user, $request->url);
        return $this->successResponse(message: __('auth.register_success'));
    }
}
