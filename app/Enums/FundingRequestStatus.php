<?php

namespace App\Enums;

enum FundingRequestStatus: string
{
    case SUBMITTED = 'submitted';
    case REVIEWED = 'reviewed';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case DISBURSED = 'disbursed';
    case SETTLED = 'settled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
