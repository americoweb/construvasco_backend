<?php

namespace App\Models\Credits;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditBalance extends Model
{
    protected $fillable = [
        'user_id', 'balance', 'lifetime_purchased', 'lifetime_consumed',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasEnough(int $amount): bool
    {
        return $this->balance >= $amount;
    }
}
