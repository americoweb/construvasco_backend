<?php

namespace App\Models\Construction;

use Illuminate\Database\Eloquent\Model;

class ProjectInvoice extends Model
{
    protected $fillable = [
        'project_id',
        'invoice_number',
        'amount',
        'currency',
        'issued_at',
        'due_at',
        'status',
        'line_items',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_at' => 'date',
        'line_items' => 'array',
    ];
}
