<?php

namespace App\Models\Candidate;

use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\TenantScoped;

class CandidateEducation extends Model
{
    use Tenantable;

    protected $fillable = [
        'candidate_id',
        'tenant_id',
        'institution_name',
        'degree_type',
        'field_of_study',
        'start_date',
        'end_date',
        'gpa',
        'honors',
        'description'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'gpa' => 'decimal:2'
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
