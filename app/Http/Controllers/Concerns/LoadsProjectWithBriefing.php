<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Resources\ManagerProjectRequestDetailResource;
use App\Http\Resources\ProjectDeliverableResource;
use App\Models\Project;

trait LoadsProjectWithBriefing
{
    protected function projectWithBriefing(int $id): Project
    {
        return Project::with([
            'client',
            'milestones',
            'deliverables.uploader',
            'deliverables.approver',
            'assignments.assignedUser',
            'projectRequest.approvedAiGeneration',
            'projectRequest.documents',
        ])->findOrFail($id);
    }

    /** @return array<string, mixed> */
    protected function projectPayload(Project $project): array
    {
        $data = $project->toArray();
        $request = $project->projectRequest;

        if ($request) {
            $data['project_request'] = (new ManagerProjectRequestDetailResource($request))->resolve();
        }

        if ($project->relationLoaded('deliverables')) {
            $data['deliverables'] = ProjectDeliverableResource::collection($project->deliverables)->resolve();
        }

        return $data;
    }
}
