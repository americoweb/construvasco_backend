<?php

namespace App\Models\JobCard;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\JobCard\JobCardFileType;
use App\Models\User;

class JobCardFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_card_id',
        'uploaded_by',
        'type',
        'file_name',
        'file_url',
        'mime_type',
        'file_size',
        'version',
        'notes',
        'disk',
        'disk_path',
        'drive_file_id',
        'drive_link',
        'drive_download_link',
        'drive_synced_at',
    ];

    protected $casts = [
        'type'           => JobCardFileType::class,
        'version'        => 'integer',
        'file_size'      => 'integer',
        'drive_synced_at' => 'datetime',
    ];

    public function getIsDriveSyncedAttribute(): bool
    {
        return $this->drive_file_id !== null;
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
