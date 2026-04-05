<?php

namespace App\Services\Product;

use App\Models\Product\Product;
use App\Models\Product\ProductSizeRestriction;

class ProductSizeValidationService
{
    public function validateCustomSize(Product $product, float $widthCm, float $heightCm): array
    {
        $errors = [];
        $restriction = $product->sizeRestrictions()->first();

        if (!$restriction) {
            return $errors; // No restrictions, allow any size
        }

        $errors = $restriction->validateDimensions($widthCm, $heightCm);

        return $errors;
    }

    public function isValidCustomSize(Product $product, float $widthCm, float $heightCm): bool
    {
        $errors = $this->validateCustomSize($product, $widthCm, $heightCm);
        return empty($errors);
    }

    public function getValidationErrors(Product $product, float $widthCm, float $heightCm): array
    {
        return $this->validateCustomSize($product, $widthCm, $heightCm);
    }
}

