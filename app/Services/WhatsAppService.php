<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $baseUrl;
    private string $apiKey;
    private string $bearerToken;
    private string $instanceName;

    public function __construct()
    {
        $this->baseUrl      = rtrim(config('services.whatsapp.url'), '/');
        $this->apiKey       = config('services.whatsapp.api_key', '');
        $this->bearerToken  = config('services.whatsapp.bearer_token', '');
        $this->instanceName = config('services.whatsapp.instance_name', '');
    }

    /**
     * Send a plain-text WhatsApp message.
     *
     * @param  string $to      Phone in international format, e.g. 258841234567
     * @param  string $message Plain text body
     * @return bool
     */
    public function send(string $to, string $message): bool
    {
        if ((empty($this->apiKey) && empty($this->bearerToken)) || empty($this->instanceName)) {
            Log::warning('WhatsAppService: Auth credentials or instance name not configured. Skipping send.', compact('to'));
            return false;
        }

        // Normalise number: strip leading + or spaces
        $to = preg_replace('/[^0-9]/', '', $to);

        $payload = [
            'action'        => 'send',
            'instance_name' => $this->instanceName,
            'to'            => $to,
            'message'       => $message,
        ];

        $endpoint = "{$this->baseUrl}/whatsapp/send";

        Log::info('[WhatsApp] Sending message', [
            'endpoint' => $endpoint,
            'payload'  => $payload,
        ]);
        echo "\n[WhatsApp] POST {$endpoint}\n";
        echo "[WhatsApp] Payload: " . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

        try {
            $headers = [
                'Content-Type' => 'application/json',
            ];

            // API Gateway supports either x-api-key or Authorization Bearer token.
            if (!empty($this->bearerToken)) {
                $headers['Authorization'] = 'Bearer ' . $this->bearerToken;
            } else {
                $headers['x-api-key'] = $this->apiKey;
            }

            $httpClient = Http::withHeaders($headers)->timeout(60);

            // Local SSL handling for WAMP/Windows development.
            // In local env, use local CA bundle if present; otherwise disable verification
            // to prevent cURL error 60 during development only.
            if (app()->environment('local')) {
                $certPath = 'C:\\wamp64\\bin\\php\\php8.2.18\\extras\\ssl\\cacert.pem';

                if (file_exists($certPath)) {
                    $httpClient = $httpClient->withOptions([
                        'verify' => $certPath,
                    ]);
                } else {
                    Log::warning('[WhatsApp] Local CA bundle not found. Disabling SSL verification for local development.', [
                        'cert_path' => $certPath,
                    ]);

                    $httpClient = $httpClient->withOptions([
                        'verify' => false,
                    ]);
                }
            }

            $response = $httpClient->post($endpoint, $payload);

            $status = $response->status();
            $body   = $response->body();

            echo "[WhatsApp] Response status: {$status}\n";
            echo "[WhatsApp] Response body: {$body}\n";

            if ($response->successful()) {
                Log::info('[WhatsApp] Message sent successfully', [
                    'to'     => $to,
                    'status' => $status,
                    'body'   => $body,
                ]);
                return true;
            }

            Log::warning('[WhatsApp] Send failed', [
                'to'     => $to,
                'status' => $status,
                'body'   => $body,
                'instance_name' => $this->instanceName,
            ]);

            if ($status === 400 && str_contains(mb_strtolower($body), 'acesso negado à instância')) {
                Log::warning('[WhatsApp] Instance access denied. Check WHATSAPP_INSTANCE_NAME and session status in Tsemba dashboard.', [
                    'instance_name' => $this->instanceName,
                ]);
            }
            return false;
        } catch (\Throwable $e) {
            echo "[WhatsApp] Exception: " . $e->getMessage() . "\n";
            Log::error('[WhatsApp] HTTP error: ' . $e->getMessage(), compact('to'));
            return false;
        }
    }
}
