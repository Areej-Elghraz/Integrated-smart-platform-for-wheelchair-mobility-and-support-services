<?php

namespace App\Http\Controllers;

use App\Models\MedicalCondition;
use Illuminate\Http\JsonResponse;

class MedicalConditionController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $conditions = MedicalCondition::all()->map(function ($cond) {
            return [
                'id' => $cond->id,
                'name' => $cond->name,
            ];
        });

        return response()->json([
            'data' => $conditions
        ]);
    }
}
