<?php

namespace App\Models\Construction;

use Illuminate\Database\Eloquent\Model;

class ProjectEstimate extends Model
{
    protected $fillable = [
        'project_id',
        'source',
        'estimated_cost',
        'estimated_duration_days',
        'cost_breakdown',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'cost_breakdown' => 'array',
    ];
}
