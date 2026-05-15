<?php

namespace App\Models\Construction;

use Illuminate\Database\Eloquent\Model;

class ProjectMilestone extends Model
{
    protected $fillable = [
        'project_id',
        'project_template_phase_id',
        'title',
        'description',
        'due_date',
        'completed_at',
        'status',
        'sort_order',
        'order_position',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'date',
    ];

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Project::class);
    }

    public function templatePhase(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProjectTemplatePhase::class, 'project_template_phase_id');
    }
}
