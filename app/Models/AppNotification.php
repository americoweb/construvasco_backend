<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'action_url',
        'reference_type', 'reference_id', 'read_at',
        'sent_via_whatsapp', 'sent_via_email',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'sent_via_whatsapp' => 'boolean',
        'sent_via_email' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
