<?php

namespace App\Http\Requests\JobCard;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\JobCard\JobCardFileType;

class UploadJobCardFileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // Restrict new uploads to the three active business categories.
        $types = implode(',', [
            JobCardFileType::BRIEFING->value,
            JobCardFileType::DESIGN->value,
            JobCardFileType::FINAL->value,
        ]);
        return [
            'file'    => 'required|file|max:51200', // 50 MB
            'type'    => "required|in:{$types}",
            'notes'   => 'nullable|string|max:500',
            'version' => 'nullable|integer|min:1|max:99',
        ];
    }
}
