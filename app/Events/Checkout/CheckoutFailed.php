<?php

namespace App\Events\Checkout;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckoutFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $cartId,
        public string $reason,
        public array $context = []
    ) {}
}
