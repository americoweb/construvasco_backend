<?php

namespace App\Models\Construction;

use Illuminate\Database\Eloquent\Model;

class ProjectDeliverable extends Model
{
    protected $fillable = [
        'project_id',
        'project_milestone_id',
        'uploaded_by',
        'deliverable_type',
        'title',
        'file_path',
        'file_disk',
        'file_format',
        'mime_type',
        'size_bytes',
        'original_name',
        'version',
        'status',
    ];
}
