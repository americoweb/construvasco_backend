<?php

namespace App\Models\Construction;

use App\Enums\ProjectPaymentType;
use App\Models\Credits\CreditPackage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPayment extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'type',
        'credit_package_id',
        'provider',
        'reference',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'type' => ProjectPaymentType::class,
        'metadata' => 'array',
        'paid_at' => 'datetime',
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
}
