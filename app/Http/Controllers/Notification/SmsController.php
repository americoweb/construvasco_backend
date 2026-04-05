<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SmsController extends Controller
{
    public function sendSms(Request $request)
    {
        // Log the request
        Log::info('SMS Request: ' . json_encode($request->all()));
        
        $request->validate([
            'phone_number' => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            $account_sid = env('TWILIO_SID');
            $auth_token = env('TWILIO_AUTH_TOKEN');
            $alphanumeric_id = env('TWILIO_ALPHANUMERIC_ID', 'IHRM');
            
            // Check if Twilio credentials are configured
            if (empty($account_sid) || empty($auth_token)) {
                Log::warning('Twilio credentials not configured. SMS not sent.', [
                    'phone_number' => $request->phone_number,
                    'message_preview' => substr($request->message, 0, 50)
                ]);
                
                // Return success but log that SMS was skipped
                return response()->json([
                    'success' => true,
                    'message' => 'SMS skipped (Twilio not configured)',
                    'data' => [
                        'skipped' => true,
                        'reason' => 'Twilio credentials not configured'
                    ]
                ], 200);
            }
            
            $client = new Client($account_sid, $auth_token);
            $message = $client->messages->create(
                $request->phone_number,
                [
                    'from' => $alphanumeric_id,
                    'body' => $request->message
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'SMS enviado com sucesso!',
                'data' => [
                    'sid' => $message->sid,
                    'status' => $message->status,
                    'to' => $message->to,
                    'from' => $message->from,
                    'body' => $message->body,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('SMS sending error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar SMS.',
                'data' => [
                    'error' => $e->getMessage(),
                ]
            ], 500);
        }
    }

    /**
     * Send tenant invitation SMS
     */
    public function sendInvitationSms($invitation, $tenant, $inviter)
    {
        try {
            $message = $this->buildInvitationMessage($invitation, $tenant, $inviter);
            
            $result = $this->sendSms(new Request([
                'phone_number' => $invitation->identifier,
                'message' => $message
            ]));
            
            // If SMS was skipped due to missing credentials, log it but don't fail
            if (is_object($result) && $result->getData(true)['data']['skipped'] ?? false) {
                Log::info('Invitation SMS skipped - Twilio not configured. Invitation still created successfully.', [
                    'invitation_id' => $invitation->id,
                    'identifier' => $invitation->identifier
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            // Log error but don't throw - invitation should still be created
            Log::warning('Failed to send invitation SMS, but invitation was created: ' . $e->getMessage(), [
                'invitation_id' => $invitation->id,
                'identifier' => $invitation->identifier
            ]);
            
            // Return a success response so invitation creation doesn't fail
            return response()->json([
                'success' => true,
                'message' => 'Invitation created (SMS skipped)',
                'data' => [
                    'skipped' => true,
                    'reason' => 'SMS sending failed: ' . $e->getMessage()
                ]
            ], 200);
        }
    }

    /**
     * Build invitation SMS message
     */
    private function buildInvitationMessage($invitation, $tenant, $inviter)
    {
        $inviteUrl = env('FRONTEND_URL', 'http://localhost:4200') . "/auth/register?invitation={$invitation->token}";
        
        return "Olá! Você foi convidado por {$inviter->name} para se juntar à equipe {$tenant->name}. Para aceitar: {$inviteUrl}";
    }
}
