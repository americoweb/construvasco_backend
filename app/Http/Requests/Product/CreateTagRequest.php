<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class CreateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da tag é obrigatório',
            'name.max' => 'O nome da tag não pode ter mais de 255 caracteres',
            'color.regex' => 'A cor deve estar no formato hexadecimal (#RRGGBB)',
        ];
    }
}

