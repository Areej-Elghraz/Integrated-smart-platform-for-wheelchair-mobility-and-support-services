<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class EnsureUserIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && is_null($user->email_verified_at)) {
            if (auth('sanctum')->check()) {
                auth('sanctum')->user()->tokens()->delete();
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Please verify your account using the OTP sent to you before proceeding.',
                'errors' => []
            ], 403);
        }

        return $next($request);
    }
}
