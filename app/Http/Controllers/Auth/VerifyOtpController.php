<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;

class VerifyOtpController extends \App\Http\Controllers\ApiController
{
    public function __invoke(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user->otp !== $request->otp) {
            return $this->errorResponse('Invalid OTP code', 400);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return $this->errorResponse('OTP code has expired', 400);
        }

        // Verify user
        $user->email_verified_at = now();
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        // Issue token
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse(
            message: 'Email verified successfully',
            parameters: [
                'user' => $user,
                'token' => $token,
            ]
        );
    }
}
