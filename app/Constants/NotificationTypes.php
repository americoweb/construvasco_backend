<?php

namespace App\Constants;

final class NotificationTypes
{
    public const REQUEST_SUBMITTED = 'request_submitted';
    public const REQUEST_UNDER_REVIEW = 'request_under_review';
    public const QUOTE_RECEIVED = 'quote_received';
    public const QUOTE_ACCEPTED = 'quote_accepted';
    public const QUOTE_REJECTED = 'quote_rejected';
    public const PROJECT_STARTED = 'project_started';
    public const PROJECT_PHASE_COMPLETED = 'project_phase_completed';
    public const PROJECT_READY_FOR_PAYMENT = 'project_ready_for_payment';
    public const PAYMENT_CONFIRMED = 'payment_confirmed';
    public const DELIVERABLES_AVAILABLE = 'deliverables_available';
    public const CREDITS_LOW = 'credits_low';
    public const CREDITS_PURCHASED = 'credits_purchased';
}
