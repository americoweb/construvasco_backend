<?php

namespace App\Http\Requests\Design;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => 'required|file|mimes:png,jpg,jpeg,webp|max:10240', // 10MB max
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'A imagem é obrigatória',
            'image.file' => 'O arquivo deve ser uma imagem válida',
            'image.mimes' => 'A imagem deve ser PNG, JPG, JPEG ou WebP',
            'image.max' => 'A imagem não pode exceder 10MB',
        ];
    }
}
