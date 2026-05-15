<?php

namespace App\Models\Construction;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTemplate extends Model
{
    protected $fillable = [
        'name', 'project_type', 'tipologia', 'description', 'is_active', 'sort_order',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectTemplatePhase::class)->orderBy('order_position');
    }
}
