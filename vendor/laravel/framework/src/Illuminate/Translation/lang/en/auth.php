<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed'   => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    // Custom authentication messages.
    'unauthorized_no_token'      => 'Unauthorized: No token.',
    'unauthorized_token_expired' => 'Unauthorized: Token expired.',
    'unauthorized'               => 'Unauthorized!',
    'forbidden_action'           => 'You are not allowed to perform this action.',
    'csrf_token_mismatch'        => 'The session has expired. Please refresh the page and try again.',
    'email_not_verified'         => 'Your email address is not verified.',
    'account_not_verified'       => 'Your account is not verified!',
    'invalid_ability'            => 'You do not have the required ability to perform this action.',
    'invalid_scope'              => 'You do not have the required scope to access this resource.',

    // Success messages.
    'register_success'        => 'Welcome to ChairPal! Please verify your email with the code we’ve sent to you.',
    'login_success'           => 'Logged in successfully.',
    'logout_success'          => 'You have been logged out successfully.',
    'password_reset_success'  => 'Your password has been reset successfully.',
    'sent_success'            => 'The :attribute has been sent to your email.',
    'verified_success'        => 'The :attribute has been verified successfully.',
    'token_generated_success' => 'Access token generated successfully.',

    // verification
    'already_verified'            => 'Your email is already verified — no need to do it again!',
    'no_verification_code'        => 'We couldn’t find a verification code. Try requesting a new one.',
    'invalid_code'                => 'Oops! That code doesn’t seem right. Please check and try again.',
    'code_expired'                => 'That code has expired. Let’s send you a new one!',
    'unauthorized_code'           => 'Sorry, you’re not allowed to verify this code.',
    'email_verified_successfully' => 'Awesome! Your email has been verified successfully.',
    'verification_code_resent'    => 'Verification code has been resent successfully.',

    // Error messages.accordion  
    'expired'            => ':attribute expired. resend :attribute and try again!',
    'already_resent'     => ':attribute already sent to your email!',
    'wait_before_resend' => 'Please wait :remain_seconds second(s) before resending the :attribute.',
    'must_verify_first'  => 'You must verify your :attribute before resetting your password.',
];
