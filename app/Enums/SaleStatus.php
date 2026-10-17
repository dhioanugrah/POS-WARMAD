<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Completed = 'completed';
    case Void = 'void';
}
