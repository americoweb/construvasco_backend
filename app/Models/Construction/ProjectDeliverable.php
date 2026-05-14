<?php

namespace App\Models\Construction;

use Illuminate\Database\Eloquent\Model;

class ProjectDeliverable extends Model
{
    protected $fillable = [
        'project_id',
        'uploaded_by',
        'deliverable_type',
        'title',
        'file_path',
        'file_format',
        'version',
        'status',
    ];
}
