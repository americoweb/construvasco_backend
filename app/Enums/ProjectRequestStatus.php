<?php

namespace App\Enums;

enum ProjectRequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Quoted = 'quoted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case ConvertedToProject = 'converted_to_project';
    case ExecutionQuoteRequested = 'execution_quote_requested';
    case Closed = 'closed';
}
