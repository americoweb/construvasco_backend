<?php

namespace App\Models\Construction;

/**
 * Client briefing / pedido inicial.
 *
 * Transições:
 * draft → submitted → under_review → quoted → approved → converted_to_project
 * quoted → quote_rejected (orçamento recusado; gestor pode reenviar) → quoted
 * quoted → rejected | submitted → cancelled
 */
use App\Enums\ProjectRequestStatus;
use App\Models\AI\AiGeneration;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'reference_code', 'project_type', 'tipologia', 'title', 'description',
        'area_m2', 'largura_m', 'comprimento_m', 'num_pisos', 'num_quartos',
        'orcamento_estimado_mt', 'prazo_desejado', 'localizacao', 'estilo_arquitectonico',
        'paleta_acabamento', 'zona_prioritaria', 'whatsapp', 'observacoes', 'briefing_data', 'reference_files',
        'approved_ai_generation_id', 'status', 'submitted_at', 'reviewed_at', 'converted_project_id',
    ];

    protected $casts = [
        'status' => ProjectRequestStatus::class,
        'reference_files' => 'array',
        'briefing_data' => 'array',
        'prazo_desejado' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'orcamento_estimado_mt' => 'decimal:2',
        'area_m2' => 'decimal:2',
        'largura_m' => 'decimal:2',
        'comprimento_m' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedAiGeneration(): BelongsTo
    {
        return $this->belongsTo(AiGeneration::class, 'approved_ai_generation_id');
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AiGeneration::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function convertedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'converted_project_id');
    }
}
