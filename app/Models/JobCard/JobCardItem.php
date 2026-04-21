<?php

namespace App\Models\JobCard;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product\Product;
use App\Models\Product\ProductColor;
use App\Models\Product\ProductSize;

class JobCardItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_card_id',
        'product_id',
        'product_color_id',
        'product_size_id',
        'product_type',
        'quantity',
        'size',
        'material',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productColor(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class);
    }

    public function productSize(): BelongsTo
    {
        return $this->belongsTo(ProductSize::class);
    }
}
