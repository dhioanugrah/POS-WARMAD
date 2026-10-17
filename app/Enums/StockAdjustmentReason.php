<?php

namespace App\Enums;

enum StockAdjustmentReason: string
{
    case Damaged = 'damaged';
    case Lost = 'lost';
    case Expired = 'expired';
    case OwnUse = 'own_use';
    case Other = 'other';
}
