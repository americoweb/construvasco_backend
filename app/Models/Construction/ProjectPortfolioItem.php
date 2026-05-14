<?php

namespace App\Models\Construction;

use Illuminate\Database\Eloquent\Model;

class ProjectPortfolioItem extends Model
{
    protected $fillable = [
        'project_id',
        'title',
        'summary',
        'media',
        'is_public',
        'published_at',
    ];

    protected $casts = [
        'media' => 'array',
        'is_public' => 'boolean',
        'published_at' => 'datetime',
    ];
}
