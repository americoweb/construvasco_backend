<?php

namespace App\Policies;

use App\Models\Construction\ProjectAssignment;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['customer', 'project_manager', 'technician', 'admin']);
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->hasRole(['admin', 'project_manager'])) {
            return true;
        }

        if ($user->hasRole('customer')) {
            return $project->client_user_id === $user->id;
        }

        if ($user->hasRole('technician')) {
            return ProjectAssignment::where('project_id', $project->id)
                ->where('assigned_to', $user->id)
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasRole(['admin', 'project_manager', 'technician']);
    }
}
