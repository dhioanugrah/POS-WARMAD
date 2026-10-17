<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Qris = 'qris';
    case Transfer = 'transfer';
    case DebitCard = 'debit_card';
    case CreditCard = 'credit_card';
    case Other = 'other';
}
