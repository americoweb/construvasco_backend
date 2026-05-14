<?php

namespace App\Services\Construction;

use App\Models\Construction\ProjectAssignment;
use App\Models\Construction\ProjectDeliverable;
use App\Models\Construction\ProjectPayment;
use App\Models\Project;

class ProjectFlowService
{
    public function createProject(array $data): Project
    {
        return Project::create($data);
    }

    public function assignProject(Project $project, int $assignedTo, ?int $assignedBy): ProjectAssignment
    {
        return ProjectAssignment::create([
            'project_id' => $project->id,
            'assigned_by' => $assignedBy,
            'assigned_to' => $assignedTo,
            'role' => 'project_manager',
            'status' => 'active',
            'assigned_at' => now(),
        ]);
    }

    public function updateProjectStatus(Project $project, string $status): Project
    {
        $project->update([
            'status' => $status,
            'current_phase' => $status,
        ]);

        return $project->fresh();
    }

    public function addDeliverable(Project $project, array $data): ProjectDeliverable
    {
        $data['project_id'] = $project->id;
        return ProjectDeliverable::create($data);
    }

    public function createPayment(Project $project, array $data): ProjectPayment
    {
        $data['project_id'] = $project->id;
        return ProjectPayment::create($data);
    }
}
