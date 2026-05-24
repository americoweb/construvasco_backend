<?php

namespace App\Models\Construction;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDeliverable extends Model
{
    protected $fillable = [
        'project_id',
        'project_milestone_id',
        'uploaded_by',
        'approved_by',
        'approved_at',
        'deliverable_type',
        'title',
        'description',
        'file_path',
        'file_disk',
        'file_format',
        'mime_type',
        'size_bytes',
        'original_name',
        'version',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'size_bytes' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
