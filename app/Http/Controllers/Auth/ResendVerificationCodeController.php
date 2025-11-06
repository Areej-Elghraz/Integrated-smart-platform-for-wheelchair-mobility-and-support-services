<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\ResendVerificationCodeRequest;
use App\Models\User;
use App\Services\GenerateCodeService;

use function PHPUnit\Framework\returnCallback;

class ResendVerificationCodeController extends ApiController
{
    public function __invoke(ResendVerificationCodeRequest $request, GenerateCodeService $generateCodeService)
    {
        $user = User::firstWhere('email', $request->email);
        $generateCodeService->verificationCode($user, $request->url);
        return $this->successResponse(__('auth.verification_code_resent'));
    }
}
