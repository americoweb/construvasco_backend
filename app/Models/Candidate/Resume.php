<?php

namespace App\Models\Candidate;

use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\TenantScoped;
use App\Traits\HasUuid;

class Resume extends Model
{
    use Tenantable, HasUuid, HasFactory;

    protected $fillable = [
        'uuid',
        'candidate_id',
        'tenant_id',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'status',
        'parsed_data',
        'confidence_score',
        'parsing_errors',
        'reparsed_at'
    ];

    protected $casts = [
        'parsed_data' => 'array',
        'parsing_errors' => 'array',
        'confidence_score' => 'decimal:2',
        'file_size' => 'integer',
        'reparsed_at' => 'datetime'
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
