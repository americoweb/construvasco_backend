<?php

namespace App\Services\Product;

use App\Models\Product\Product;
use App\Models\Product\ProductSize;
use App\Enums\Product\PricingType;

class ProductPricingService
{
    public function calculatePrice(Product $product, ?ProductSize $size = null, ?float $widthCm = null, ?float $heightCm = null): float
    {
        return $product->calculateSizePrice($size, $widthCm, $heightCm);
    }

    public function calculatePriceWithDetails(Product $product, ?ProductSize $size = null, ?float $widthCm = null, ?float $heightCm = null): array
    {
        $price = $this->calculatePrice($product, $size, $widthCm, $heightCm);
        $areaSqm = 0;
        $unitPrice = 0;

        if ($product->pricing_type === PricingType::SQM_BASED) {
            if ($size && $size->width_cm && $size->height_cm) {
                $areaSqm = ($size->width_cm * $size->height_cm) / 10000;
            } elseif ($widthCm && $heightCm) {
                $areaSqm = ($widthCm * $heightCm) / 10000;
            }

            $unitPrice = $product->price_per_sqm ?? 0;
        }

        return [
            'price' => round($price, 2),
            'area_sqm' => round($areaSqm, 4),
            'unit_price' => $unitPrice,
            'pricing_type' => $product->pricing_type->value,
        ];
    }
}

