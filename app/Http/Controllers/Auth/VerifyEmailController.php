<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VerifyEmailController extends ApiController
{
    public function __invoke(VerifyEmailRequest $request)
    {
        $user = User::where('email', $request->email)->firstOrFail();

        // Check if already verified
        if ($user->email_verified_at) {
            return ValidationException::withMessages(['email' => __('auth.already_verified')]);
        }

        // Ensure verification code exists
        if (!$user->email_verification_code) {
            return ValidationException::withMessages(['email' => __('auth.no_verification_code')]);
        }

        // Calculate expiration
        $expiresAt = $user->email_verification_code_expires_at;
        if (!$expiresAt) {
            return ValidationException::withMessages(['code' => __('auth.invalid_code')]);
        }

        $secondsPassed = now()->diffInSeconds($expiresAt, false); // false => negative if expired
        $expiration    = config('auth.verification_codes.users.expire', 60);

        if ($secondsPassed < 0) {
            return ValidationException::withMessages(['code' => __('auth.code_expired')]); // 410 Gone
        }

        // Verify code
        if (!Hash::check($request->code, $user->email_verification_code)) {
            return ValidationException::withMessages(['code' => __('auth.invalid_code')]);
        }

        // Update user
        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
            'email_verification_times_sent' => 0,
        ])->save();

        return $this->successResponse(__('auth.email_verified_successfully'));
    }
}
