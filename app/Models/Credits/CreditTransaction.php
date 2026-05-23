<?php

namespace App\Models\Credits;

use App\Enums\CreditTransactionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransaction extends Model
{
    protected $fillable = [
        'user_id', 'type', 'amount', 'balance_after',
        'reference_type', 'reference_id', 'notes', 'granted_by',
    ];

    protected $casts = [
        'type' => CreditTransactionType::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
