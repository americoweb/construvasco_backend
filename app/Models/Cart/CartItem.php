<?php

namespace App\Models\Cart;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product\Product;
use App\Models\Product\ProductColor;
use App\Models\Product\ProductPrintArea;
use App\Models\Design\Design;
use App\Traits\HasUuid;

class CartItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'cart_id',
        'product_id',
        'design_id',
        'product_color_id',
        'product_print_area_id',
        'quantity',
        'unit_price',
        'total_price',
        'design_prompt',
        'mockup_url',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($item) {
            $item->total_price = $item->unit_price * $item->quantity;
        });
        
        static::updating(function ($item) {
            $item->total_price = $item->unit_price * $item->quantity;
        });
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class, 'product_color_id');
    }

    public function printArea(): BelongsTo
    {
        return $this->belongsTo(ProductPrintArea::class, 'product_print_area_id');
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_price, 2, ',', '.') . ' MT';
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2, ',', '.') . ' MT';
    }

    public function updateQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
        $this->total_price = $this->unit_price * $quantity;
        $this->save();
    }
}
