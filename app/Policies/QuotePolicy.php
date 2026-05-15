<?php

namespace App\Policies;

use App\Models\Construction\Quote;
use App\Models\User;

class QuotePolicy
{
    public function view(User $user, Quote $quote): bool
    {
        if ($user->hasRole(['admin', 'project_manager'])) {
            return true;
        }

        return $user->hasRole('customer')
            && $quote->projectRequest?->user_id === $user->id;
    }

    public function accept(User $user, Quote $quote): bool
    {
        return $user->hasRole('customer')
            && $quote->projectRequest?->user_id === $user->id;
    }
}
