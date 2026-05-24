<?php

namespace App\Enums;

enum DeliverableStatus: string
{
    case SubmittedForReview = 'submitted_for_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /** @return list<string> */
    public static function pendingReviewValues(): array
    {
        return [self::SubmittedForReview->value, 'submitted'];
    }
}
