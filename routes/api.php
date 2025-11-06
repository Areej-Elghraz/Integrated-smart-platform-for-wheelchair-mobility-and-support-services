<?php

use App\Http\Controllers\Auth\ForgetPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResendOtpController;
use App\Http\Controllers\Auth\ResendVerificationCodeController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\VerifyOtpController;
use App\TokenAbilityEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/register', RegisterController::class)->name('auth.register');
Route::post('/login', LoginController::class)->name('auth.login');

Route::post('/forget-password', ForgetPasswordController::class)->name('auth.forget-password');
Route::post('/resend-otp', ResendOtpController::class)->name('auth.resend-otp');
Route::post('/verify-otp', VerifyOtpController::class)->name('auth.verify-otp');
Route::post('/reset-password', ResetPasswordController::class)->name('auth.reset-password');
Route::post('/resend-verification-code', ResendVerificationCodeController::class)->name('auth.resend-verification-code');
Route::post('/verify-email', VerifyEmailController::class)->name('auth.verify-email');

// Route::post('/logout', LogoutController::class)->name('auth.logout')->middleware(['auth:sanctum', 'verified', 'ability:' . TokenAbilityEnum::ACCESS_TOKEN->value]);

Route::middleware(['auth:sanctum', 'verified', 'ability:' . TokenAbilityEnum::ACCESS_TOKEN->value])->group(function () {
  Route::post('/logout', LogoutController::class)->name('auth.logout');
});
