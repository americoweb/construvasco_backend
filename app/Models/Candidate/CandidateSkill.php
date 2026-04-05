<?php

namespace App\Models\Candidate;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Enums\Candidate\SkillLevel;

class CandidateSkill extends Pivot
{
    protected $table = 'candidate_skills';

    protected $fillable = [
        'candidate_id',
        'skill_id',
        'level',
        'years_experience',
        'verified',
        'source'
    ];

    protected $casts = [
        'level' => SkillLevel::class,
        'years_experience' => 'integer',
        'verified' => 'boolean'
    ];
}
