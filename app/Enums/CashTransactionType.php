<?php

namespace App\Enums;

enum CashTransactionType: string
{
    case In = 'in';
    case Out = 'out';
}
