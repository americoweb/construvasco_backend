<?php

namespace App\Enums;

enum ProjectPaymentType: string
{
    case CreditsPurchase = 'credits_purchase';
    case ProjectFinal = 'project_final';
}
