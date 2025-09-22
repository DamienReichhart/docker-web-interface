<?php

namespace App\Enum;

enum UserRoleEnum: string
{
    case ADMIN = 'ADMIN';
    case USER = 'USER';
    case WAITING = 'WAITING';

    public static function toString(UserRoleEnum $enumValue) : string
    {
        switch ($enumValue) {
            case self::ADMIN:
                return 'ADMIN';
            case self::USER:
                return 'USER';
            case self::WAITING:
            default:
                return 'WAITING';
        }
    }
}