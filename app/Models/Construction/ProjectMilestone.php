<?php

namespace App\Models\Construction;

use Illuminate\Database\Eloquent\Model;

class ProjectMilestone extends Model
{
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'due_date',
        'completed_at',
        'status',
        'sort_order',
    ];
}
