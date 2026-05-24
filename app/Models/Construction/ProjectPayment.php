<?php

namespace App\Models\Construction;

use App\Enums\ProjectPaymentPhase;
use App\Enums\ProjectPaymentStatus;
use App\Enums\ProjectPaymentType;
use App\Models\Credits\CreditPackage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProjectPayment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'project_id',
        'user_id',
        'type',
        'phase',
        'credit_package_id',
        'provider',
        'reference',
        'transaction_id',
        'provider_reference',
        'phone_number',
        'amount',
        'currency',
        'status',
        'paid_at',
        'metadata',
        'notes',
        'proof_path',
        'proof_uploaded_at',
        'confirmed_by',
        'confirmed_at',
        'rejected_reason',
    ];

    protected $casts = [
        'type' => ProjectPaymentType::class,
        'phase' => ProjectPaymentPhase::class,
        'status' => ProjectPaymentStatus::class,
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'proof_uploaded_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creditPackage(): BelongsTo
    {
        return $this->belongsTo(CreditPackage::class);
    }

    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'phase', 'confirmed_at', 'confirmed_by', 'rejected_reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
