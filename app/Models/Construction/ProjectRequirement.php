<?php

namespace App\Models\Construction;

use Illuminate\Database\Eloquent\Model;

class ProjectRequirement extends Model
{
    protected $fillable = [
        'project_id',
        'terrain_type',
        'estimated_area_m2',
        'floors',
        'rooms',
        'style_preferences',
        'technical_needs',
        'constraints',
        'raw_briefing',
    ];

    protected $casts = [
        'raw_briefing' => 'array',
    ];
}
