<?php

namespace App\Http\Requests\JobCard;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\JobCard\JobCardPriority;
use App\Enums\JobCard\ClientTier;

class CreateJobCardRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // People
            'client_id'             => 'required|integer|exists:users,id',
            'assigned_designer_id'  => 'nullable|integer|exists:users,id',

            // Brief
            'title'                 => 'required|string|max:255',
            'description'           => 'nullable|string',
            'objective'             => 'nullable|string|max:255',
            'deadline'              => 'required|date|after:now',

            // Priority
            'priority'              => 'nullable|in:' . implode(',', array_column(JobCardPriority::cases(), 'value')),
            'priority_override'     => 'nullable|boolean',
            'priority_reason'       => 'nullable|required_if:priority_override,true|string|max:500',

            // Client tier
            'client_tier'           => 'nullable|in:' . implode(',', array_column(ClientTier::cases(), 'value')),

            // Revision
            'revision_limit'        => 'nullable|integer|min:1|max:10',

            'notes'                 => 'nullable|string',

            // Items
            'items'                        => 'nullable|array',
            'items.*.product_id'           => 'nullable|integer|exists:products,id',
            'items.*.product_color_id'     => 'nullable|integer|exists:product_colors,id',
            'items.*.product_size_id'      => 'nullable|integer|exists:product_sizes,id',
            'items.*.product_type'         => 'required_with:items|string|max:255',
            'items.*.quantity'             => 'required_with:items|integer|min:1',
            'items.*.size'                 => 'nullable|string|max:100',
            'items.*.material'             => 'nullable|string|max:100',
            'items.*.notes'                => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required'        => 'O cliente é obrigatório.',
            'client_id.exists'          => 'Cliente não encontrado.',
            'title.required'            => 'O título é obrigatório.',
            'deadline.required'         => 'O prazo é obrigatório.',
            'deadline.after'            => 'O prazo deve ser uma data futura.',
            'priority_reason.required_if' => 'O motivo é obrigatório ao activar prioridade urgente.',
        ];
    }
}
