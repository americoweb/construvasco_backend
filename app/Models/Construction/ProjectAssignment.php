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
        'status',
        'assigned_at',
        'unassigned_at',
    ];
}
