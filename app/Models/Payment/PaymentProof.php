<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentProof extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'amount',
        'reference',
        'file_path',
        'bank_account',
        'transfer_date',
        'notes',
        'status', // pending_review, approved, rejected
        'reviewed_by',
        'review_notes',
        'reviewed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'transfer_date' => 'datetime',
        'reviewed_at' => 'datetime',
    ];
}

