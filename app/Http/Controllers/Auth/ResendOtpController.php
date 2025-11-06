<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Models\User;
use App\Services\GenerateCodeService;

class ResendOtpController extends ApiController
{
    public function __invoke(ResendOtpRequest $request, GenerateCodeService $generateCodeService)
    {
        $user = User::firstWhere('email', $request->email);
        $generateCodeService->otpCode($user, $request->url);
        return $this->successResponse(__('auth.verification_code_resent'));
    }
}
