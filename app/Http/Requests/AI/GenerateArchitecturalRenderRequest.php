<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class GenerateArchitecturalRenderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hasLogo = !empty($this->input('logo_base64'));
        $hasReference = !empty($this->input('reference_image_base64'));

        return [
            'design_prompt' => ($hasLogo || $hasReference)
                ? 'nullable|string|max:2000'
                : 'required|string|min:5|max:2000',
            'logo_base64' => 'nullable|string',
            'logo_mime_type' => 'required_with:logo_base64|string',
            'reference_image_base64' => 'nullable|string',
            'reference_image_mime_type' => 'required_with:reference_image_base64|string',
            'generation_id' => 'sometimes|string|max:100',
            'house_image_url' => 'sometimes|nullable|string|max:2048',
        ];
    }
}
