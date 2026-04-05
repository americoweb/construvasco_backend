<?php

namespace App\Models\Candidate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasUuid;
use App\Enums\Candidate\CandidateStatus;
use App\Enums\Candidate\ExperienceLevel;
use App\Models\Traits\Tenantable;
use App\Models\Skill;

class Candidate extends Model
{
    use Tenantable, HasUuid, HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'status',
        'source',
        'notes',
        'experience_level',
        'location',
        'availability',
        'salary_expectation',
        'preferred_work_type'
    ];

    protected $casts = [
        'status' => CandidateStatus::class,
        'experience_level' => ExperienceLevel::class,
        'salary_expectation' => 'decimal:2',
        'availability' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function profile(): HasOne
    {
        return $this->hasOne(CandidateProfile::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Skill::class,
            'candidate_skills',
            'candidate_id',
            'skill_id'
        )->withPivot('level', 'years_experience', 'verified')
             ->withTimestamps();
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(CandidateExperience::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(CandidateEducation::class);
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getLatestResumeAttribute(): ?Resume
    {
        return $this->resumes()->latest()->first();
    }
}
