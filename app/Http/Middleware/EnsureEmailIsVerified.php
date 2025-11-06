<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use function PHPUnit\Framework\returnCallback;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        // $user = auth('sanctum')->user();
        if ($user && $user->email_verified_at) {
            return $next($request);
        }
        throw new \Exception(__('auth.email_not_verified'), 403);
    }
}
