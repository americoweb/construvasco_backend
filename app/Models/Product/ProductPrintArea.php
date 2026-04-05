<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\Product\PrintAreaPosition;

class ProductPrintArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'position',
        'description',
        'max_width_cm',
        'max_height_cm',
        'additional_price',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'position' => PrintAreaPosition::class,
        'max_width_cm' => 'decimal:2',
        'max_height_cm' => 'decimal:2',
        'additional_price' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDimensionsAttribute(): string
    {
        return "{$this->max_width_cm}cm x {$this->max_height_cm}cm";
    }
}
