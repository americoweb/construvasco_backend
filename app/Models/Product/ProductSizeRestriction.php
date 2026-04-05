<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSizeRestriction extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'min_width_cm',
        'max_width_cm',
        'min_height_cm',
        'max_height_cm',
        'min_aspect_ratio',
        'max_aspect_ratio',
        'step_increment_cm',
    ];

    protected $casts = [
        'min_width_cm' => 'decimal:2',
        'max_width_cm' => 'decimal:2',
        'min_height_cm' => 'decimal:2',
        'max_height_cm' => 'decimal:2',
        'min_aspect_ratio' => 'decimal:2',
        'max_aspect_ratio' => 'decimal:2',
        'step_increment_cm' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function validateDimensions(float $widthCm, float $heightCm): array
    {
        $errors = [];

        if ($this->min_width_cm && $widthCm < $this->min_width_cm) {
            $errors[] = "Largura mínima é {$this->min_width_cm}cm";
        }

        if ($this->max_width_cm && $widthCm > $this->max_width_cm) {
            $errors[] = "Largura máxima é {$this->max_width_cm}cm";
        }

        if ($this->min_height_cm && $heightCm < $this->min_height_cm) {
            $errors[] = "Altura mínima é {$this->min_height_cm}cm";
        }

        if ($this->max_height_cm && $heightCm > $this->max_height_cm) {
            $errors[] = "Altura máxima é {$this->max_height_cm}cm";
        }

        if ($this->min_aspect_ratio || $this->max_aspect_ratio) {
            $aspectRatio = $widthCm / $heightCm;
            
            if ($this->min_aspect_ratio && $aspectRatio < $this->min_aspect_ratio) {
                $errors[] = "Proporção mínima é {$this->min_aspect_ratio}";
            }

            if ($this->max_aspect_ratio && $aspectRatio > $this->max_aspect_ratio) {
                $errors[] = "Proporção máxima é {$this->max_aspect_ratio}";
            }
        }

        if ($this->step_increment_cm) {
            $widthRemainder = fmod($widthCm, $this->step_increment_cm);
            $heightRemainder = fmod($heightCm, $this->step_increment_cm);
            
            if ($widthRemainder > 0.01 || $heightRemainder > 0.01) {
                $errors[] = "Dimensões devem ser múltiplos de {$this->step_increment_cm}cm";
            }
        }

        return $errors;
    }
}
