<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;

class ResetPasswordController extends \App\Http\Controllers\ApiController
{
    public function __invoke(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
            'password' => 'required|confirmed|string|min:6',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user->otp !== $request->otp) {
            return $this->errorResponse('Invalid OTP code', 400);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return $this->errorResponse('OTP code has expired', 400);
        }

        $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        return $this->successResponse(message: 'Password has been reset successfully.');
    }
}
