<?php

namespace App\Services;

use App\Models\TenantInvitation;
use App\Models\Settings\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendInvitation(TenantInvitation $invitation, Tenant $tenant, User $inviter): bool
    {
        try {
            if ($invitation->type === 'email') {
                app(\App\Http\Controllers\Notification\EmailController::class)
                    ->sendInvitationEmail($invitation, $tenant, $inviter);
            } else {
                // For WhatsApp/SMS invitations, try to send but don't fail if Twilio is not configured
                try {
                    $result = app(\App\Http\Controllers\Notification\SmsController::class)
                        ->sendInvitationSms($invitation, $tenant, $inviter);
                    
                    // Check if SMS was skipped
                    if (is_object($result)) {
                        $data = $result->getData(true);
                        if (isset($data['data']['skipped']) && $data['data']['skipped']) {
                            Log::info('Invitation SMS skipped (Twilio not configured), but invitation was created successfully', [
                                'invitation_id' => $invitation->id,
                                'identifier' => $invitation->identifier
                            ]);
                        }
                    }
                } catch (\Exception $smsException) {
                    // Log but don't fail - invitation should still be created
                    Log::warning('SMS invitation failed, but invitation was created: ' . $smsException->getMessage(), [
                        'invitation_id' => $invitation->id,
                        'identifier' => $invitation->identifier
                    ]);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            // For email, we might want to fail, but for SMS we should be more lenient
            if ($invitation->type === 'email') {
                Log::error('Failed to send invitation email: ' . $e->getMessage());
                return false;
            } else {
                // For SMS, log but don't fail - invitation is still created
                Log::warning('Failed to send invitation SMS, but invitation was created: ' . $e->getMessage());
                return true;
            }
        }
    }

    public function sendEmail(string $email, string $subject, string $message, array $attachments = []): bool
    {
        try {
            app(\App\Http\Controllers\Notification\EmailController::class)
                ->sendEmail(new \Illuminate\Http\Request([
                    'email' => $email,
                    'subject' => $subject,
                    'message' => $message,
                    'attachments' => $attachments
                ]));
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email: ' . $e->getMessage());
            return false;
        }
    }

    public function sendSms(string $phoneNumber, string $message): bool
    {
        try {
            app(\App\Http\Controllers\Notification\SmsController::class)
                ->sendSms(new \Illuminate\Http\Request([
                    'phone_number' => $phoneNumber,
                    'message' => $message
                ]));
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send SMS: ' . $e->getMessage());
            return false;
        }
    }
} 