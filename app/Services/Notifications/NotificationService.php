<?php

namespace App\Services\Notifications;

use App\Jobs\SendWhatsAppNotification;
use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    public function notify(User $user, string $type, array $data, array $channels = ['in_app']): AppNotification
    {
        $notification = null;

        if (in_array('in_app', $channels, true)) {
            $notification = AppNotification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $data['title'] ?? $type,
                'message' => $data['message'] ?? '',
                'action_url' => $data['action_url'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => isset($data['reference_id']) ? (string) $data['reference_id'] : null,
            ]);
        }

        if (in_array('whatsapp', $channels, true) && $user->identifier && $user->type === 'whatsapp') {
            SendWhatsAppNotification::dispatch($user->identifier, $data['message'] ?? $data['title'] ?? '');
            $notification?->update(['sent_via_whatsapp' => true]);
        }

        return $notification ?? new AppNotification([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $data['title'] ?? '',
            'message' => $data['message'] ?? '',
        ]);
    }
}
