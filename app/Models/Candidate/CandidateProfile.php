<?php

namespace App\Models\Candidate;

use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\TenantScoped;

class CandidateProfile extends Model
{
    use Tenantable, HasFactory;

    protected $fillable = [
        'candidate_id',
        'tenant_id',
        'summary',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'willing_to_relocate',
        'preferred_locations',
        'work_authorization',
        'languages',
        'certifications',
        'achievements'
    ];

    protected $casts = [
        'willing_to_relocate' => 'boolean',
        'preferred_locations' => 'array',
        'languages' => 'array',
        'certifications' => 'array',
        'achievements' => 'array'
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
