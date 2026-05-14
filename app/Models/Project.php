<?php

namespace App\Models;

use App\Models\Traits\LogsActivityWithTenant;
use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes, LogsActivityWithTenant, Tenantable; // Tenantable LAST!

    protected $fillable = [
        'tenant_id',
        'client_user_id',
        'service_category_id',
        'construction_service_id',
        'name',
        'description',
        'status',
        'project_type',
        'location',
        'start_date',
        'end_date',
        'desired_deadline',
        'budget',
        'target_budget',
        'current_phase',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'desired_deadline' => 'date',
        'budget' => 'decimal:2',
        'target_budget' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Activity log configuration
    protected static $logName = 'projects';
    protected static $logAttributes = ['name', 'description', 'status', 'budget'];
    protected static $logOnlyDirty = true;

    protected function allowTenantChange(): bool
    {
        return auth('api')->user()?->hasRole('super_admin') ?? false;
    }
}