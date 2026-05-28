<?php

namespace App\Enums;

enum UserRoleEnum: string
{
    case USER = 'user';
    case COMPANION = 'companion';
    case DOCTOR = 'doctor';
    case ORGANIZATION = 'organization';
    case ORGANIZATION_ADMIN = 'organization_admin';
    case ADMIN = 'admin';
    case SUPER_ADMIN = 'super_admin';

    public static function values()
    {
        return array_column(self::cases(), 'value');
    }
}
