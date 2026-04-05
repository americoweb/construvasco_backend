<?php

namespace App\Http\Requests\Design;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Design\DesignStatus;
use Illuminate\Validation\Rules\Enum;

class UpdateDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_color_id' => 'sometimes|exists:product_colors,id',
            'product_print_area_id' => 'sometimes|exists:product_print_areas,id',
            'prompt' => 'nullable|string|max:2000',
            'mockup_url' => 'nullable|url|max:1000',
            'status' => ['sometimes', new Enum(DesignStatus::class)],
        ];
    }
}
