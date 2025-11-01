<?php

namespace App;

enum LanguagePreferenceEnum: string
{
    case EN = 'en';
    case AR = 'ar';

    public static function values()
    {
        return array_column(self::cases(), 'value');
    }
}
