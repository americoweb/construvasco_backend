<?php

namespace App\Models\Construction;

use Illuminate\Database\Eloquent\Model;

class ProjectDocument extends Model
{
    protected $fillable = [
        'project_id',
        'document_type',
        'file_name',
        'file_path',
        'mime_type',
        'size_bytes',
        'uploaded_by',
    ];
}
