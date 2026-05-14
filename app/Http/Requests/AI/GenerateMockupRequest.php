<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMockupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // If logo or reference is provided, design_prompt is optional
        // If no logo/reference, design_prompt is required with minimum length
        $hasLogo = !empty($this->input('logo_base64'));
        $hasReference = !empty($this->input('reference_image_base64'));
        
        return [
            'product_id' => 'required|integer|exists:products,id',
            'design_prompt' => ($hasLogo || $hasReference)
                ? 'nullable|string|max:1000' 
                : 'required|string|min:5|max:1000',
            'logo_base64' => 'nullable|string',
            'logo_mime_type' => 'required_with:logo_base64|string',
            'reference_image_base64' => 'nullable|string',
            'reference_image_mime_type' => 'required_with:reference_image_base64|string',
            'color_id' => 'sometimes|integer|exists:product_colors,id',
            'print_area_id' => 'sometimes|integer|exists:product_print_areas,id',
            'generation_id' => 'sometimes|string|max:100',
            'house_image_url' => 'sometimes|nullable|string|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Selecione um produto',
            'product_id.exists' => 'Produto não encontrado',
            'design_prompt.required' => 'Descreva o design desejado, forneça um logotipo ou uma imagem de referência',
            'design_prompt.min' => 'Forneça mais detalhes sobre o design (mínimo 5 caracteres), um logotipo ou uma imagem de referência',
        ];
    }
}
