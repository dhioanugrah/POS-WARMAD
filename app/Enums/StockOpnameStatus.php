<?php

namespace App\Enums;

enum StockOpnameStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';
}
