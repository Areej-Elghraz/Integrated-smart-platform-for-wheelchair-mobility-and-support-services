<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends ApiController
{
    /**
     * Get the authenticated user's profile data.
     */
    public function show(Request $request): JsonResponse
    {
        $user = Auth::user()->load([
            'medicalConditions',
            'friends',
            'organizations',
            'wheelchairs'
        ]);

        return $this->successResponse('User profile retrieved successfully.', parameters: ['data' => $user]);
    }
}
