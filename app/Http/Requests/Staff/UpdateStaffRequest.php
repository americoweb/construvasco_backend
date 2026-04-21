<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['sometimes', 'string', 'max:255'],
            'role'      => ['sometimes', 'string', 'exists:roles,name'],
            'is_active' => ['sometimes', 'boolean'],
            'password'  => ['sometimes', 'nullable', 'string', 'min:8'],
        ];
    }
}
