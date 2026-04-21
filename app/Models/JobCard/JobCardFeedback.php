<?php

namespace App\Models\JobCard;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\JobCard\FeedbackRole;
use App\Models\User;

class JobCardFeedback extends Model
{
    use HasFactory;

    protected $table = 'job_card_feedback';

    protected $fillable = [
        'job_card_id',
        'created_by',
        'comment',
        'role',
        'version',
        'is_approved',
    ];

    protected $casts = [
        'role'        => FeedbackRole::class,
        'version'     => 'integer',
        'is_approved' => 'boolean',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
