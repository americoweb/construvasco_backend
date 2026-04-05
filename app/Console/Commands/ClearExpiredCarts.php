<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Cart\CartService;

class ClearExpiredCarts extends Command
{
    protected $signature = 'cart:clear-expired';
    
    protected $description = 'Remove expired shopping carts from the database';

    public function handle(CartService $cartService): int
    {
        $count = $cartService->clearExpiredCarts();
        
        $this->info("Removed {$count} expired carts.");
        
        return Command::SUCCESS;
    }
}
