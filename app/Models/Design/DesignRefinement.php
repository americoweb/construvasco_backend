<?php

namespace App\Models\Design;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\Design\DesignStatus;

class DesignRefinement extends Model
{
    use HasFactory;

    protected $fillable = [
        'design_id',
        'refinement_prompt',
        'previous_mockup_url',
        'new_mockup_url',
        'new_mockup_base64',
        'status',
        'ai_response_metadata',
    ];

    protected $casts = [
        'status' => DesignStatus::class,
        'ai_response_metadata' => 'array',
    ];

    protected $hidden = [
        'new_mockup_base64',
    ];

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function getNewMockupAttribute(): ?string
    {
        if ($this->new_mockup_url) {
            return $this->new_mockup_url;
        }
        
        if ($this->new_mockup_base64) {
            return 'data:image/png;base64,' . $this->new_mockup_base64;
        }
        
        return null;
    }
}
