<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class CreateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'identifier' => ['required', 'string', 'email', 'max:255', 'unique:users,identifier'],
            'role'       => ['required', 'string', 'exists:roles,name'],
            'password'   => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.unique' => 'Este e-mail já está em uso.',
            'role.exists'       => 'O perfil seleccionado não existe.',
        ];
    }
}
