<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSize extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'width_cm',
        'height_cm',
        'is_predefined',
        'is_custom',
        'fixed_price',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'width_cm' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'is_predefined' => 'boolean',
        'is_custom' => 'boolean',
        'fixed_price' => 'decimal:2',
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

    public function scopePredefined($query)
    {
        return $query->where('is_predefined', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_custom', true);
    }

    public function getAreaSqmAttribute(): float
    {
        if ($this->width_cm && $this->height_cm) {
            return ($this->width_cm * $this->height_cm) / 10000;
        }
        return 0;
    }

    public function getDimensionsAttribute(): string
    {
        if ($this->width_cm && $this->height_cm) {
            return "{$this->width_cm}cm x {$this->height_cm}cm";
        }
        return 'Custom';
    }
}
