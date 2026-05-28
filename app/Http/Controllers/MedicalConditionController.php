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
        $locale = app()->getLocale();
        $isAr = $locale === 'ar';

        $conditions = MedicalCondition::all()->map(function ($cond) use ($isAr) {
            return [
                'id' => $cond->id,
                'name' => $isAr ? $cond->name_ar : $cond->name_en,
                'category' => $isAr ? $cond->category_ar : $cond->category_en,
                'description' => $isAr ? $cond->description_ar : $cond->description_en,
            ];
        });

        return response()->json([
            'data' => $conditions
        ]);
    }
}
