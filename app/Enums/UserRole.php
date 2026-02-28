<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case OWNER = 'owner';
    case FINANCE = 'finance';
    case PURCHASING = 'purchasing';
    case ADMIN_GUDANG = 'admin_gudang';
    case SPPG_USER = 'sppg_user';
    case VENDOR_ADMIN = 'vendor_admin';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
