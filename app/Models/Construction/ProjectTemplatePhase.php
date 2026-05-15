<?php

namespace App\Models\Construction;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTemplatePhase extends Model
{
    protected $fillable = [
        'project_template_id', 'name', 'description', 'order_position',
        'estimated_days', 'is_required',
    ];

    protected $casts = ['is_required' => 'boolean'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'project_template_id');
    }
}
