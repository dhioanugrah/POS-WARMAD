<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Opening = 'opening';
    case Purchase = 'purchase';
    case PurchaseReturn = 'purchase_return';
    case PurchaseVoid = 'purchase_void';
    case Sale = 'sale';
    case SaleReturn = 'sale_return';
    case SaleVoid = 'sale_void';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Adjustment = 'adjustment';
    case Opname = 'opname';
}
