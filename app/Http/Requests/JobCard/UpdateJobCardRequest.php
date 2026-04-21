<?php

namespace App\Http\Requests\JobCard;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\JobCard\JobCardPriority;
use App\Enums\JobCard\ClientTier;

class UpdateJobCardRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'assigned_designer_id'  => 'nullable|integer|exists:users,id',
            'title'                 => 'sometimes|required|string|max:255',
            'description'           => 'nullable|string',
            'objective'             => 'nullable|string|max:255',
            'deadline'              => 'sometimes|required|date',
            'priority'              => 'nullable|in:' . implode(',', array_column(JobCardPriority::cases(), 'value')),
            'priority_override'     => 'nullable|boolean',
            'priority_reason'       => 'nullable|required_if:priority_override,true|string|max:500',
            'client_tier'           => 'nullable|in:' . implode(',', array_column(ClientTier::cases(), 'value')),
            'revision_limit'        => 'nullable|integer|min:1|max:10',
            'notes'                 => 'nullable|string',
        ];
    }
}
