<?php

namespace App\Models\Design;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Product\Product;
use App\Models\Product\ProductColor;
use App\Models\Product\ProductPrintArea;
use App\Enums\Design\DesignStatus;
use App\Traits\HasUuid;

class Design extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'session_id',
        'product_id',
        'product_color_id',
        'product_print_area_id',
        'prompt',
        'mockup_url',
        'mockup_base64',
        'logo_path',
        'logo_mime_type',
        'reference_image_path',
        'reference_mime_type',
        'status',
        'generation_attempts',
        'ai_model_used',
        'ai_response_metadata',
        'is_from_suggestion',
        'suggestion_id',
    ];

    protected $casts = [
        'status' => DesignStatus::class,
        'generation_attempts' => 'integer',
        'ai_response_metadata' => 'array',
        'is_from_suggestion' => 'boolean',
    ];

    protected $hidden = [
        'mockup_base64',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class, 'product_color_id');
    }

    public function printArea(): BelongsTo
    {
        return $this->belongsTo(ProductPrintArea::class, 'product_print_area_id');
    }

    public function refinements(): HasMany
    {
        return $this->hasMany(DesignRefinement::class)->orderBy('created_at', 'desc');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', DesignStatus::COMPLETED);
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function isCompleted(): bool
    {
        return $this->status === DesignStatus::COMPLETED || $this->status === DesignStatus::REFINED;
    }

    public function hasMockup(): bool
    {
        return !empty($this->mockup_url) || !empty($this->mockup_base64);
    }

    public function hasLogo(): bool
    {
        return !empty($this->logo_path);
    }

    public function hasReferenceImage(): bool
    {
        return !empty($this->reference_image_path);
    }

    public function getMockupAttribute(): ?string
    {
        if ($this->mockup_url) {
            return $this->mockup_url;
        }
        
        if ($this->mockup_base64) {
            return 'data:image/png;base64,' . $this->mockup_base64;
        }
        
        return null;
    }

    public function incrementGenerationAttempts(): void
    {
        $this->increment('generation_attempts');
    }
}
