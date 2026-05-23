<?php

namespace App\Models\Construction;

use App\Enums\QuoteStatus;
use App\Enums\QuoteType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quote extends Model
{
    use LogsActivity;

    protected $fillable = [
        'project_request_id', 'quote_type', 'created_by_user_id', 'total_amount_mt', 'breakdown',
        'delivery_days', 'conditions', 'status', 'sent_at', 'responded_at', 'expires_at',
        'rejection_reason', 'project_template_id',
    ];

    protected $casts = [
        'quote_type' => QuoteType::class,
        'status' => QuoteStatus::class,
        'breakdown' => 'array',
        'total_amount_mt' => 'decimal:2',
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function projectRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectRequest::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function projectTemplate(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class);
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class, 'quote_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'quote_type', 'total_amount_mt', 'responded_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
