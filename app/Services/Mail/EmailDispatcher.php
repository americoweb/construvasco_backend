<?php

namespace App\Services\Mail;

use App\Models\Mail\EmailDispatch;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class EmailDispatcher
{
    public function dispatchIdempotent(
        string $eventType,
        User $target,
        Mailable $mailable,
        string $relatedEntityType,
        int|string $relatedEntityId,
    ): bool {
        $exists = EmailDispatch::query()
            ->where('event_type', $eventType)
            ->where('related_entity_type', $relatedEntityType)
            ->where('related_entity_id', (string) $relatedEntityId)
            ->exists();

        if ($exists) {
            return false;
        }

        Mail::to($target->identifier)->send($mailable);

        EmailDispatch::create([
            'event_type' => $eventType,
            'target_user_id' => $target->id,
            'related_entity_type' => $relatedEntityType,
            'related_entity_id' => (string) $relatedEntityId,
            'sent_at' => now(),
        ]);

        return true;
    }
}
