<?php

namespace App\Listeners\Checkout;

use App\Events\Checkout\CheckoutFailed;
use Illuminate\Support\Facades\Log;

class LogCheckoutFailure
{
    public function handle(CheckoutFailed $event): void
    {
        Log::channel('checkout')->error('Checkout failed', [
            'cart_id' => $event->cartId,
            'reason' => $event->reason,
            'context' => $event->context,
        ]);
    }
}
