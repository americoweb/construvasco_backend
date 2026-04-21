<?php

namespace App\Http\Requests\JobCard;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\JobCard\FeedbackRole;

class AddFeedbackRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'comment'     => 'required|string|max:2000',
            'role'        => 'required|in:' . implode(',', array_column(FeedbackRole::cases(), 'value')),
            'is_approved' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'O comentário é obrigatório.',
            'role.required'    => 'O papel (role) é obrigatório.',
        ];
    }
}
