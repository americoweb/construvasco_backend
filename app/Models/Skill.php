<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\Tenantable;

class Skill extends Model
{
    use Tenantable, HasFactory;

    protected $fillable = [
        'name',
        'category',
        'description',
        'is_active',
        'tenant_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Candidate\Candidate::class,
            'candidate_skills',
            'skill_id',
            'candidate_id'
        )->withPivot('level', 'years_experience', 'verified')
         ->withTimestamps();
    }
} 