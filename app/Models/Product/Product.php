<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Enums\Product\ProductStatus;
use App\Enums\Product\PricingType;
use App\Traits\HasUuid;
use App\Models\Product\Testimonial;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'price',
        'min_quantity',
        'image_url',
        'base_image_url',
        'design_hint',
        'status',
        'is_featured',
        'sort_order',
        'pricing_type',
        'price_per_sqm',
        'has_sizes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'min_quantity' => 'integer',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'status' => ProductStatus::class,
        'pricing_type' => PricingType::class,
        'price_per_sqm' => 'decimal:2',
        'has_sizes' => 'boolean',
    ];

    public function colors(): HasMany
    {
        return $this->hasMany(ProductColor::class)->orderBy('sort_order');
    }

    public function printAreas(): HasMany
    {
        return $this->hasMany(ProductPrintArea::class)->orderBy('sort_order');
    }

    public function activeColors(): HasMany
    {
        return $this->hasMany(ProductColor::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function activePrintAreas(): HasMany
    {
        return $this->hasMany(ProductPrintArea::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag')
            ->withTimestamps();
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class)->orderBy('sort_order');
    }

    public function activeTestimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class)->orderBy('sort_order');
    }

    public function activeSizes(): HasMany
    {
        return $this->hasMany(ProductSize::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public function sizeRestrictions(): HasMany
    {
        return $this->hasMany(ProductSizeRestriction::class);
    }

    public function hasSizes(): bool
    {
        return $this->has_sizes ?? false;
    }

    public function calculateSizePrice(?ProductSize $size = null, ?float $widthCm = null, ?float $heightCm = null): float
    {
        if (!$this->hasSizes()) {
            return $this->price;
        }

        if ($this->pricing_type === PricingType::SQM_BASED) {
            if ($size && $size->width_cm && $size->height_cm) {
                $areaSqm = ($size->width_cm * $size->height_cm) / 10000;
            } elseif ($widthCm && $heightCm) {
                $areaSqm = ($widthCm * $heightCm) / 10000;
            } else {
                return $this->price;
            }

            return ($this->price_per_sqm ?? 0) * $areaSqm;
        }

        // Fixed pricing
        if ($size && $size->fixed_price !== null) {
            return $size->fixed_price;
        }

        return $this->price;
    }

    public function getPriceForSize(?ProductSize $size = null, ?float $widthCm = null, ?float $heightCm = null): float
    {
        return $this->calculateSizePrice($size, $widthCm, $heightCm);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ProductStatus::ACTIVE);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function calculateTotal(int $quantity): float
    {
        return $this->price * max($quantity, $this->min_quantity);
    }

    public function isAvailable(): bool
    {
        return $this->status === ProductStatus::ACTIVE;
    }
}
