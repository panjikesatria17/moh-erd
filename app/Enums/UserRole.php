<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case OWNER = 'owner';
    case FINANCE = 'finance';
    case PURCHASING = 'purchasing';
    case ADMIN_GUDANG = 'admin_gudang';
    case EXPEDITION = 'expedition';
    case SPPG_USER = 'sppg_user';
    case VENDOR_ADMIN = 'vendor_admin';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::SUPER_ADMIN->value => 'Super Admin',
            self::ADMIN->value => 'Admin',
            self::OWNER->value => 'Owner',
            self::FINANCE->value => 'Finance',
            self::PURCHASING->value => 'Purchasing',
            self::ADMIN_GUDANG->value => 'Admin Gudang',
            self::EXPEDITION->value => 'Ekspedisi',
            self::SPPG_USER->value => 'SPPG User',
            self::VENDOR_ADMIN->value => 'Vendor Admin',
        ];
    }
}
