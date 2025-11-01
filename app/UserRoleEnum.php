<?php

namespace App;

enum UserRoleEnum: string
{
    case USER = 'user';
    case PROVIDER = 'provider';
    case SPECIALIST = 'specialist';
    case ADMIN = 'admin';

    public static function values()
    {
        return array_column(self::cases(), 'value');
    }
}
