<?php

namespace App\Models\Credits;

use Illuminate\Database\Eloquent\Model;

class CreditPackage extends Model
{
    protected $fillable = [
        'name', 'credits_amount', 'price_mt', 'is_active', 'sort_order', 'description',
    ];

    protected $casts = [
        'price_mt' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
