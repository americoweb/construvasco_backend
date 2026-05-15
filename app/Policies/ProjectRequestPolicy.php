<?php

namespace App\Policies;

use App\Enums\ProjectRequestStatus;
use App\Models\Construction\ProjectRequest;
use App\Models\User;

class ProjectRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['customer', 'project_manager', 'admin']);
    }

    public function view(User $user, ProjectRequest $request): bool
    {
        if ($user->hasRole(['admin', 'project_manager'])) {
            return true;
        }

        return $user->hasRole('customer') && $request->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('customer');
    }

    public function update(User $user, ProjectRequest $request): bool
    {
        if ($user->hasRole(['admin', 'project_manager'])) {
            return true;
        }

        return $user->hasRole('customer')
            && $request->user_id === $user->id
            && $request->status === ProjectRequestStatus::Draft;
    }

    public function delete(User $user, ProjectRequest $request): bool
    {
        return $this->update($user, $request);
    }
}
