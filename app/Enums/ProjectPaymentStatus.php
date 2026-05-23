<?php

namespace App\Enums;

enum ProjectPaymentStatus: string
{
    case Pending = 'pending';
    case ProofSubmitted = 'proof_submitted';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
}
