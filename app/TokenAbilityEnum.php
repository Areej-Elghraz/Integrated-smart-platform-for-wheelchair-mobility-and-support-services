<?php

namespace App;

enum TokenAbilityEnum: string
{
    case ACCESS_TOKEN = 'access_token';
    case REMEMBER_TOKEN = 'remember_token';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
