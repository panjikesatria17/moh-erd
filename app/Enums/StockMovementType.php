<?php

namespace App\Enums;

enum StockMovementType: string
{
    case IN = 'in';
    case OUT = 'out';
    case ADJUSTMENT = 'adjustment';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
