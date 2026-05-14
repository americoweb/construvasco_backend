<?php

namespace App\Models\Construction;

use Illuminate\Database\Eloquent\Model;

class ProjectPayment extends Model
{
    protected $fillable = [
        'project_id',
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
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];
}
