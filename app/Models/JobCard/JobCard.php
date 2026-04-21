<?php

namespace App\Models\JobCard;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\JobCard\JobCardStatus;
use App\Enums\JobCard\JobCardPriority;
use App\Enums\JobCard\ClientTier;
use App\Models\User;
use App\Models\Order\Order;
use App\Traits\HasUuid;
use Illuminate\Support\Str;

class JobCard extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'uuid',
        'job_number',
        'client_id',
        'created_by',
        'assigned_designer_id',
        'status',
        'title',
        'description',
        'objective',
        'deadline',
        'priority',
        'priority_override',
        'priority_reason',
        'priority_score',
        'client_tier',
        'revision_limit',
        'revision_count',
        'order_id',
        'notes',
    ];

    protected $casts = [
        'status'            => JobCardStatus::class,
        'priority'          => JobCardPriority::class,
        'client_tier'       => ClientTier::class,
        'priority_override' => 'boolean',
        'priority_score'    => 'integer',
        'revision_limit'    => 'integer',
        'revision_count'    => 'integer',
        'deadline'          => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (JobCard $jobCard) {
            if (empty($jobCard->job_number)) {
                $jobCard->job_number = self::generateJobNumber();
            }
            if (empty($jobCard->status)) {
                $jobCard->status = JobCardStatus::DRAFT;
            }
        });

        // Recalculate priority score before every save
        static::saving(function (JobCard $jobCard) {
            $jobCard->priority_score = $jobCard->computePriorityScore();
        });
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_designer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(JobCardItem::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(JobCardFile::class)->orderBy('created_at', 'desc');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(JobCardFeedback::class)->orderBy('created_at', 'asc');
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeByStatus($query, JobCardStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [JobCardStatus::DONE, JobCardStatus::CANCELLED]);
    }

    public function scopeByPriority($query)
    {
        return $query->orderByDesc('priority_score');
    }

    public function scopeByClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeByDesigner($query, int $designerId)
    {
        return $query->where('assigned_designer_id', $designerId);
    }

    // -----------------------------------------------------------------------
    // Business logic helpers
    // -----------------------------------------------------------------------

    public function isDraft(): bool       { return $this->status === JobCardStatus::DRAFT; }
    public function isDesign(): bool      { return $this->status === JobCardStatus::DESIGN; }
    public function isRevision(): bool    { return $this->status === JobCardStatus::REVISION; }
    public function isApproval(): bool    { return $this->status === JobCardStatus::APPROVAL; }
    public function isProduction(): bool  { return $this->status === JobCardStatus::PRODUCTION; }
    public function isDone(): bool        { return $this->status === JobCardStatus::DONE; }
    public function isCancelled(): bool   { return $this->status === JobCardStatus::CANCELLED; }

    public function canBeCancelled(): bool
    {
        return !in_array($this->status, [JobCardStatus::DONE, JobCardStatus::CANCELLED]);
    }

    public function isOverRevisionLimit(): bool
    {
        return $this->revision_count >= $this->revision_limit;
    }

    public function isOverdue(): bool
    {
        return $this->deadline->isPast() && $this->status->isActive();
    }

    /**
     * Priority score formula:
     * (priority_weight * 100) + override_bonus + vip_bonus - hours_until_deadline
     *
     * Higher score = higher urgency = appears first in list/kanban
     */
    public function computePriorityScore(): int
    {
        $weight          = ($this->priority ?? JobCardPriority::MEDIUM)->weight();
        $overrideBonus   = $this->priority_override ? 500 : 0;
        $tierBonus       = ($this->client_tier ?? ClientTier::NORMAL)->scoreBonus();
        $hoursRemaining  = $this->deadline ? now()->diffInHours($this->deadline, false) : 0;
        // Negative hours = overdue → subtracting a negative = adding points (overdue pops to top)

        return ($weight * 100) + $overrideBonus + $tierBonus - (int) $hoursRemaining;
    }

    // -----------------------------------------------------------------------
    // Generators
    // -----------------------------------------------------------------------

    public static function generateJobNumber(): string
    {
        $year    = now()->format('Y');
        $prefix  = "JC-{$year}-";
        $last    = self::where('job_number', 'like', "{$prefix}%")
                       ->orderBy('job_number', 'desc')
                       ->value('job_number');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
