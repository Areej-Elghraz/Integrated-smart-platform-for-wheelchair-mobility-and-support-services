<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Wheelchair;

class VerifyWheelchairApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('api-key');

        if (!$apiKey) {
            return response()->json(['message' => 'Missing api-key header.'], 401);
        }

        $wheelchair = Wheelchair::where('api_key', $apiKey)->first();

        if (!$wheelchair) {
            return response()->json(['message' => 'Invalid api-key.'], 401);
        }

        // Attach wheelchair to request so we can access it in controller without re-querying
        $request->merge(['authenticated_wheelchair' => $wheelchair]);

        return $next($request);
    }
}
