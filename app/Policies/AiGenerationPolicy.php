<?php

namespace App\Policies;

use App\Models\AI\AiGeneration;
use App\Models\User;

class AiGenerationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['customer', 'admin']);
    }

    public function view(User $user, AiGeneration $generation): bool
    {
        return $user->hasRole('admin') || $generation->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['customer', 'admin']);
    }
}
