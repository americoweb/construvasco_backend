<?php

namespace App\Models;

use App\Enums\ProjectContractPhase;
use App\Support\ProjectContractPhaseLabel;
use App\Models\Construction\ProjectAssignment;
use App\Models\Construction\ProjectDeliverable;
use App\Models\Construction\ProjectInvoice;
use App\Models\Construction\ProjectMilestone;
use App\Models\Construction\ProjectPayment;
use App\Models\Construction\ProjectRequest;
use App\Models\Construction\ProjectTemplate;
use App\Models\Construction\Quote;
use App\Models\Traits\LogsActivityWithTenant;
use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes, LogsActivityWithTenant, Tenantable;

    protected $fillable = [
        'tenant_id', 'client_user_id', 'project_request_id', 'quote_id', 'construction_quote_id',
        'project_template_id', 'service_category_id', 'construction_service_id', 'name', 'description',
        'status', 'contract_phase', 'architecture_completed_at', 'construction_completed_at',
        'construction_quote_requested_at', 'construction_request_notes', 'suggested_site_visit_date',
        'project_type', 'location', 'start_date', 'end_date', 'desired_deadline',
        'budget', 'target_budget', 'current_phase', 'client_can_download', 'final_payment_status',
    ];

    protected $appends = [
        'contract_phase_label',
    ];

    protected $casts = [
        'contract_phase' => ProjectContractPhase::class,
        'architecture_completed_at' => 'datetime',
        'construction_completed_at' => 'datetime',
        'construction_quote_requested_at' => 'datetime',
        'suggested_site_visit_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'desired_deadline' => 'date',
        'budget' => 'decimal:2',
        'target_budget' => 'decimal:2',
        'client_can_download' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static $logName = 'projects';
    protected static $logAttributes = [
        'name', 'description', 'status', 'budget', 'contract_phase', 'architecture_completed_at',
    ];
    protected static $logOnlyDirty = true;

    public function getContractPhaseLabelAttribute(): string
    {
        return ProjectContractPhaseLabel::for($this->contract_phase);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function projectRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectRequest::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function constructionQuote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'construction_quote_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'project_template_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('order_position')->orderBy('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(ProjectDeliverable::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ProjectPayment::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(ProjectInvoice::class);
    }

    public function getProgressPercentageAttribute(): int
    {
        $total = $this->milestones()->count();
        if ($total === 0) {
            return 0;
        }
        $done = $this->milestones()->where('status', 'completed')->count();

        return (int) round(($done / $total) * 100);
    }

    /** Marco técnico activo (não confundir com coluna DB current_phase). */
    public function getActiveMilestoneAttribute(): ?ProjectMilestone
    {
        return $this->milestones()
            ->whereIn('status', ['in_progress', 'pending'])
            ->orderBy('order_position')
            ->first();
    }

    public function getMainTechnicianAttribute(): ?User
    {
        $assignment = $this->assignments()
            ->where('assignment_role', 'main')
            ->where('status', 'active')
            ->first();

        return $assignment?->assignedUser;
    }

    public function getCollaboratorsAttribute()
    {
        return $this->assignments()
            ->where('assignment_role', 'collaborator')
            ->where('status', 'active')
            ->with('assignedUser')
            ->get()
            ->pluck('assignedUser')
            ->filter();
    }

    protected function allowTenantChange(): bool
    {
        return auth('api')->user()?->hasRole('admin') ?? false;
    }
}
