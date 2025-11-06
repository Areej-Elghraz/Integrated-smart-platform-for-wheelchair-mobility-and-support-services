<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends ApiController
{

    public function __invoke(ResetPasswordRequest $request)
    {
        $validated = $request->validated();
        $user      = User::where('email', $request->email)?->first();
        $otpRecord = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages(['email' => __('validation.invalid_value', ['attribute' => __('validation.attributes.email')])]);
        }

        if (!$otpRecord || empty($otpRecord->verified) || !$otpRecord->verified) {
            throw new \Exception(__('messages.must_verify_otp_first'), 403);
        }

        if (Hash::check($validated['new_password'], $user->password)) {
            throw ValidationException::withMessages(['new_password' => __('validation.new_password_must_differ')]);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        /// Fire reset event (optional)
        event(new PasswordReset($user));

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->delete();

        $user->tokens()?->where('name', $user->currentAccessToken()?->name)->delete();

        return $this->successResponse(__('messages.password_reset'));
    }
}
