<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\ForgetPasswordRequest;
use App\Models\User;
use App\Services\GenerateCodeService;

class ForgetPasswordController extends ApiController
{
    public function __invoke(ForgetPasswordRequest $request, GenerateCodeService $generateCodeService)
    {
        $user = User::where('email', $request->email)->first();
        $generateCodeService->otpCode($user, $request->url);
        return $this->successResponse(message: __('auth.sent_success', ['attribute' => __('validation.attributes.otp')]));
    }
}
