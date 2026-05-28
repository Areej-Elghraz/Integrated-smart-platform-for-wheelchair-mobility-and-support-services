<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\GenerateCodeService;
use App\Services\UserService;

class RegisterController extends ApiController
{
    public function __invoke(RegisterRequest $request, UserService $userService)
    {
        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('avatars', 'public');
        }

        $user = $userService->createUser($request->validated(), $path);

        // // Automatically verify email since OTP is removed
        // $user->email_verified_at = now();
        // $user->save();

        // Handle Friendship / Link Requests
        $validated = $request->validated();
        if ($user->role === 'companion' && !empty($validated['target_username'])) {
            $targetUser = \App\Models\User::where('username', $validated['target_username'])->first();
            if ($targetUser) {
                \App\Models\Friendship::create([
                    'user_id' => $user->id,
                    'friend_id' => $targetUser->id,
                    'status' => 'pending',
                ]);
            }
        } elseif ($user->role === 'user' && !empty($validated['doctor_username'])) {
            $doctorUser = \App\Models\User::where('username', $validated['doctor_username'])->where('role', 'doctor')->first();
            if ($doctorUser) {
                \App\Models\Friendship::create([
                    'user_id' => $user->id,
                    'friend_id' => $doctorUser->id,
                    'status' => 'pending',
                ]);
            }
        }

        if ($user->role === 'user' && !empty($validated['medical_condition_ids'])) {
            $user->medicalConditions()->sync($validated['medical_condition_ids']);
        }

        // Generate OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        // Send OTP Email
        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\SendOtpMail($otp, $user->name));

        // Registration is complete but the account is not yet verified.
        // The user must confirm the OTP sent to their email before being able to log in.
        return $this->successResponse(
            message: 'Registration successful. Please verify your email using the OTP sent.',
            parameters: ['email' => $user->email]
        );
    }
}
