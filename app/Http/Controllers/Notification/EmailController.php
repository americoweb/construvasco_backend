<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use SendGrid;
use SendGrid\Mail\Attachment;
use SendGrid\Mail\Mail as SendGridMail;

class EmailController extends Controller
{
    public function sendEmail(Request $request)
    {
        // ONLY FOR DEVELOPMENT - REMOVE IN PRODUCTION
        if (env('APP_ENV') !== 'production') {
            \Illuminate\Support\Facades\Config::set('mail.verify_peer', false);
            \Illuminate\Support\Facades\Config::set('mail.verify_peer_name', false);
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
            'attachments' => 'nullable|array',
        ]);

        try {
            $email = new SendGridMail();
            $email->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $email->setSubject($validated['subject']);
            $email->addTo($validated['email']);
            $email->addContent("text/plain", $validated['message']);
            
            // Handle attachments if present
            if ($request->has('attachments') && is_array($request->attachments)) {
                foreach ($request->attachments as $attachmentData) {
                    if (isset($attachmentData['content']) && isset($attachmentData['filename'])) {
                        $attachment = new Attachment();
                        $attachment->setContent($attachmentData['content']);
                        $attachment->setFilename($attachmentData['filename']);
                        $attachment->setType($attachmentData['type'] ?? 'application/pdf');
                        $attachment->setDisposition($attachmentData['disposition'] ?? 'attachment');
                        $email->addAttachment($attachment);
                    }
                }
            }
            
            $sendgrid = new SendGrid(env('MAIL_PASSWORD'));
            
            // Configure SendGrid client with cURL options to handle SSL issues in development
            $options = [
                'curl' => [
                    CURLOPT_SSL_VERIFYHOST => env('APP_ENV') === 'production' ? 2 : 0,
                    CURLOPT_SSL_VERIFYPEER => env('APP_ENV') === 'production' ? true : false,
                ]
            ];
            
            $response = $sendgrid->client->mail()->send()->post($email, $options);
            
            return response()->json([
                'success' => true,
                'message' => 'E-mail enviado com sucesso!',
                'data' => [
                    'status' => $response->statusCode(),
                    'subject' => $validated['subject'],
                    'to' => $validated['email'],
                    'body' => $validated['message'],
                    'has_attachments' => !empty($request->attachments)
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Email sending error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar e-mail.',
                'data' => [
                    'error' => $e->getMessage(),
                ]
            ], 500);
        }
    }

    /**
     * Send tenant invitation email
     */
    public function sendInvitationEmail($invitation, $tenant, $inviter)
    {
        $subject = "Convite para {$tenant->name}";
        $message = $this->buildInvitationMessage($invitation, $tenant, $inviter);
        
        return $this->sendEmail(new Request([
            'email' => $invitation->identifier,
            'subject' => $subject,
            'message' => $message
        ]));
    }

    /**
     * Build invitation email message
     */
    private function buildInvitationMessage($invitation, $tenant, $inviter)
    {
        $inviteUrl = env('FRONTEND_URL', 'http://localhost:4200') . "/auth/register?invitation={$invitation->token}";
        
        return "
Olá!

Você foi convidado por {$inviter->name} para se juntar à equipe {$tenant->name}.

Para aceitar o convite, clique no link abaixo:
{$inviteUrl}

Se você não esperava este convite, pode ignorá-lo.

Atenciosamente,
Equipe {$tenant->name}
        ";
    }
}
