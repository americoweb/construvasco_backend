<?php

namespace App\Listeners\Checkout;

use App\Events\Checkout\CheckoutCompleted;
use App\Jobs\Checkout\SendOrderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCheckoutNotifications implements ShouldQueue
{
    public function handle(CheckoutCompleted $event): void
    {
        // Dispatch WhatsApp notification job
        SendOrderNotification::dispatch($event->order)->delay(now()->addSeconds(30));
    }
}
