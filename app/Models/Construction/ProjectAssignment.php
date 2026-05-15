<?php

namespace App\Models\Construction;

use Illuminate\Database\Eloquent\Model;

class ProjectAssignment extends Model
{
    protected $fillable = [
        'project_id',
        'assigned_by',
        'assigned_to',
        'role',
        'assignment_role',
        'status',
        'assigned_at',
        'unassigned_at',
    ];

    public function assignedUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Project::class);
    }
}
