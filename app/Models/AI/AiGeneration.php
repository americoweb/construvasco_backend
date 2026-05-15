<?php

namespace App\Models\AI;

use App\Enums\AiGenerationStatus;
use App\Enums\AiGenerationType;
use App\Models\Construction\ProjectRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneration extends Model
{
    protected $fillable = [
        'user_id', 'project_request_id', 'type', 'prompt', 'parameters',
        'image_url', 'image_path', 'status', 'credits_consumed', 'error_message',
        'provider', 'parent_generation_id',
    ];

    protected $casts = [
        'type' => AiGenerationType::class,
        'status' => AiGenerationStatus::class,
        'parameters' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projectRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectRequest::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_generation_id');
    }
}
