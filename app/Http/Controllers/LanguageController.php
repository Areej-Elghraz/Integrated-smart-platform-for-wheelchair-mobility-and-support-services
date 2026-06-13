<?php

namespace App\Http\Controllers;

use App\Enums\LanguagePreferenceEnum;
use Illuminate\Http\Request;

class LanguageController extends ApiController
{
    public function index(Request $request)
    {
        $search = strtolower($request->input('search', ''));

        $languages = collect(LanguagePreferenceEnum::cases())->map(function ($enum) {
            return [
                'code' => $enum->value,
                'name' => match ($enum) {
                    LanguagePreferenceEnum::EN => 'English',
                    LanguagePreferenceEnum::AR => 'Arabic',
                    LanguagePreferenceEnum::FR => 'French',
                    LanguagePreferenceEnum::GE => 'German',
                    LanguagePreferenceEnum::HI => 'Hindi',
                    LanguagePreferenceEnum::KO => 'Korean',
                    LanguagePreferenceEnum::VI => 'Vietnamese',
                },
            ];
        });
        // $languages = collect(LanguagePreferenceEnum::cases())->map(function ($enum) {
        //     return [
        //         'code' => $enum->value,
        //         'name' => $enum->name, // EN, AR, etc. You can map to actual names if needed
        //     ];
        // });

        if ($search) {
            $languages = $languages->filter(function ($lang) use ($search) {
                return str_contains(strtolower($lang['code']), $search) || str_contains(strtolower($lang['name']), $search);
            });
        }

        return $this->successResponse('Languages retrieved successfully', parameters: $languages->values()->toArray());
    }
}
