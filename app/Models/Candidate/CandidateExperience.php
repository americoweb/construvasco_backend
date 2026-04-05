<?php

namespace App\Models\Candidate;

use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\TenantScoped;

class CandidateExperience extends Model
{
    use Tenantable;

    protected $fillable = [
        'candidate_id',
        'tenant_id',
        'company_name',
        'job_title',
        'description',
        'start_date',
        'end_date',
        'is_current',
        'location',
        'achievements',
        'technologies_used'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'achievements' => 'array',
        'technologies_used' => 'array'
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
