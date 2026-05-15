<?php

namespace App\Services\Construction;

use App\Models\Construction\ProjectMilestone;
use App\Models\Construction\ProjectTemplate;
use App\Models\Project;
use Carbon\Carbon;

class ProjectTemplateService
{
    public function instantiateMilestones(Project $project, ProjectTemplate $template): void
    {
        $position = 0;
        foreach ($template->phases as $phase) {
            ProjectMilestone::create([
                'project_id' => $project->id,
                'project_template_phase_id' => $phase->id,
                'title' => $phase->name,
                'description' => $phase->description,
                'status' => 'pending',
                'sort_order' => $position,
                'order_position' => $phase->order_position ?? $position,
                'due_date' => $phase->estimated_days
                    ? Carbon::now()->addDays($phase->estimated_days)
                    : null,
            ]);
            $position++;
        }
    }
}
