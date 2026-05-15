<?php

namespace App\Enums;

enum CreditTransactionType: string
{
    case InitialGrant = 'initial_grant';
    case Purchase = 'purchase';
    case Consumption = 'consumption';
    case ManualGrant = 'manual_grant';
    case Refund = 'refund';
}
